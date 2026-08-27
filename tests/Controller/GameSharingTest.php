<?php

namespace App\Tests\Controller;

use App\Game\Infrastructure\GameCardRenderer;
use App\Tests\Support\FunctionalTestCase;

/**
 * What a pasted ticker link turns into: the metadata an unfurler reads, and
 * the scoreboard image behind it.
 */
class GameSharingTest extends FunctionalTestCase
{
    public function testTheGamePageCarriesTheSharingMetadata(): void
    {
        $game = $this->createGame($this->createUser('owner@example.com'), 'meta-vs-tags-2026-12-01', homepoints: 3, awaypoints: 1);

        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSame('Falcons 3 : 1 Sharks', $this->metaContent($crawler, 'og:title'));
        $this->assertStringContainsString('Stadthalle', $this->metaContent($crawler, 'og:description'));
        $this->assertStringEndsWith('/games/' . $game->getSlug(), $this->metaContent($crawler, 'og:url'));
        $this->assertSame(
            $this->metaContent($crawler, 'og:url'),
            $crawler->filter('link[rel="canonical"]')->attr('href'),
        );
    }

    public function testTheCardIsAnnouncedAndDeliveredInTheExpectedSize(): void
    {
        if (!static::getContainer()->get(GameCardRenderer::class)->isAvailable()) {
            $this->markTestSkipped('No GD with FreeType and a usable font on this machine.');
        }

        $game = $this->createGame($this->createUser('owner@example.com'), 'card-vs-preview-2026-12-01');

        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());
        $announced = $this->metaContent($crawler, 'og:image');

        $this->assertStringContainsString('/games/' . $game->getSlug() . '/card.png?v=', $announced);
        $this->assertSame('summary_large_image', $crawler->filter('meta[name="twitter:card"]')->attr('content'));

        $this->client->request('GET', parse_url($announced, PHP_URL_PATH));

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'image/png');

        $size = getimagesizefromstring($this->client->getResponse()->getContent());
        $this->assertSame([1200, 630], [$size[0], $size[1]]);
    }

    public function testTheAnnouncedCardUrlChangesWithTheScore(): void
    {
        $game = $this->createGame($this->createUser('owner@example.com'), 'bust-vs-cache-2026-12-01');

        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());
        $before = $this->metaContent($crawler, 'og:image');

        $game->setHomepoints(1);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());

        // Without a moving URL, unfurlers would keep showing the old score.
        $this->assertNotSame($before, $this->metaContent($crawler, 'og:image'));
    }

    public function testADeactivatedGameIsGoneForThePublicImageIncluded(): void
    {
        $game = $this->createGame($this->createUser('owner@example.com'), 'hidden-vs-public-2026-12-01', active: false);

        $this->client->request('GET', '/games/' . $game->getSlug());
        $this->assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/games/' . $game->getSlug() . '/card.png');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testTheOwnerStillReachesTheirDeactivatedGame(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'hidden-vs-owner-2026-12-01', active: false);

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());

        $this->assertResponseIsSuccessful();
        // Reachable, but nothing a search engine should keep.
        $this->assertSame('noindex', $crawler->filter('meta[name="robots"]')->attr('content'));
    }

    private function metaContent(\Symfony\Component\DomCrawler\Crawler $crawler, string $property): string
    {
        $selector = str_starts_with($property, 'og:')
            ? sprintf('meta[property="%s"]', $property)
            : sprintf('meta[name="%s"]', $property);

        return $crawler->filter($selector)->attr('content');
    }
}

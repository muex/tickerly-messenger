<?php

namespace App\Tests\Controller;

use App\Game\Infrastructure\GameCardRenderer;
use App\Tests\Support\FunctionalTestCase;

/**
 * What a crawler and an unfurler get to see on the landing page.
 */
class LandingPageTest extends FunctionalTestCase
{
    public function testItCarriesTitleDescriptionAndCanonical(): void
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Live-Ticker', $crawler->filter('title')->text());
        $this->assertNotEmpty($crawler->filter('meta[name="description"]')->attr('content'));
        $this->assertStringEndsWith('/', $crawler->filter('link[rel="canonical"]')->attr('href'));
        $this->assertCount(1, $crawler->filter('link[rel="canonical"]'));
        $this->assertCount(1, $crawler->filter('h1'));
    }

    public function testItDescribesItselfForSharingAndForSearchEngines(): void
    {
        $crawler = $this->client->request('GET', '/');

        $this->assertSame('website', $crawler->filter('meta[property="og:type"]')->attr('content'));
        $this->assertNotEmpty($crawler->filter('meta[property="og:title"]')->attr('content'));

        $data = json_decode($crawler->filter('script[type="application/ld+json"]')->text(), true);

        $this->assertSame('WebSite', $data['@type']);
        $this->assertSame('de-DE', $data['inLanguage']);
    }

    public function testItLinksToTheGamesItKnowsAbout(): void
    {
        $game = $this->createGame($this->createUser('owner@example.com'), 'landing-vs-link-2026-12-01');

        $crawler = $this->client->request('GET', '/');

        // The list on /games is built by JavaScript, so without these links a
        // crawler has no way from the landing page into any game page.
        $this->assertCount(1, $crawler->filter('a[href="/games/' . $game->getSlug() . '"]'));
    }

    public function testItKeepsDeactivatedGamesToItself(): void
    {
        $game = $this->createGame($this->createUser('owner@example.com'), 'landing-vs-hidden-2026-12-01', active: false);

        $crawler = $this->client->request('GET', '/');

        $this->assertCount(0, $crawler->filter('a[href="/games/' . $game->getSlug() . '"]'));
    }

    public function testTheSiteCardIsDelivered(): void
    {
        if (!static::getContainer()->get(GameCardRenderer::class)->isAvailable()) {
            $this->markTestSkipped('No GD with FreeType and a usable font on this machine.');
        }

        $this->client->request('GET', '/card.png');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'image/png');

        $size = getimagesizefromstring($this->client->getResponse()->getContent());
        $this->assertSame([1200, 630], [$size[0], $size[1]]);
    }
}

<?php

namespace App\Tests\Controller;

use App\Game\Infrastructure\GameCardRenderer;
use Symfony\Component\DomCrawler\Crawler;
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

        $types = $this->structuredData($crawler);

        $this->assertSame('de-DE', $types['WebSite']['inLanguage']);
        $this->assertSame('SportsApplication', $types['WebApplication']['applicationCategory']);
        $this->assertSame('0', $types['WebApplication']['offers']['price']);
    }

    public function testTheAnsweredQuestionsAreTheOnesOnThePage(): void
    {
        $crawler = $this->client->request('GET', '/');
        $asked = $crawler->filter('details summary')->each(static fn (Crawler $node): string => $node->text());

        $this->assertNotEmpty($asked);

        // Marked-up answers that are not on the page are exactly what the
        // FAQPage guidelines forbid, so both come from one list in the template.
        $marked = array_map(
            static fn (array $entry): string => $entry['name'],
            $this->structuredData($crawler)['FAQPage']['mainEntity'],
        );

        $this->assertSame($asked, $marked);
    }

    public function testTheListedGamesAreMarkedUpAsFixtures(): void
    {
        $game = $this->createGame($this->createUser('owner@example.com'), 'markup-vs-fixture-2026-12-01');

        $crawler = $this->client->request('GET', '/');
        $events = $this->structuredData($crawler)['ItemList']['itemListElement'];

        $this->assertContains($game->getHome() . ' : ' . $game->getAway(), array_column($events, 'name'));
        $this->assertSame('Falcons', $events[0]['homeTeam']['name']);
    }

    public function testItNamesTheSportsItSupports(): void
    {
        $crawler = $this->client->request('GET', '/');
        $text = $crawler->filter('body')->text();

        // The reason anyone searching for "handball live ticker" would ever
        // land here.
        $this->assertStringContainsString('Handball', $text);
        $this->assertStringContainsString('American Football', $text);
    }

    /**
     * Every ld+json block on the page, keyed by its @type — which also asserts
     * that each one is valid JSON.
     *
     * @return array<string, array<string, mixed>>
     */
    private function structuredData(Crawler $crawler): array
    {
        $blocks = [];

        foreach ($crawler->filter('script[type="application/ld+json"]') as $node) {
            $data = json_decode($node->textContent, true, flags: JSON_THROW_ON_ERROR);
            $blocks[$data['@type']] = $data;
        }

        return $blocks;
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

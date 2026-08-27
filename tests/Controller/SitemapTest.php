<?php

namespace App\Tests\Controller;

use App\Tests\Support\FunctionalTestCase;

class SitemapTest extends FunctionalTestCase
{
    public function testItListsThePublicPagesAsXml(): void
    {
        $game = $this->createGame($this->createUser('owner@example.com'), 'sitemap-vs-listed-2026-12-01');

        $this->client->request('GET', '/sitemap.xml');
        $body = $this->client->getResponse()->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/xml; charset=UTF-8');

        $xml = new \SimpleXMLElement($body);
        $locations = [];
        foreach ($xml->url as $url) {
            $locations[] = (string) $url->loc;
        }

        $this->assertContains('http://localhost/', $locations);
        $this->assertContains('http://localhost/games', $locations);
        $this->assertContains('http://localhost/games/' . $game->getSlug(), $locations);
    }

    public function testItLeavesOutDeactivatedGames(): void
    {
        $game = $this->createGame($this->createUser('owner@example.com'), 'sitemap-vs-hidden-2026-12-01', active: false);

        $this->client->request('GET', '/sitemap.xml');

        // They answer 404 to everyone but their owner; pointing a crawler at
        // them would only produce soft errors.
        $this->assertStringNotContainsString($game->getSlug(), $this->client->getResponse()->getContent());
    }
}

<?php

namespace App\Tests\Controller;

use App\Tests\Support\FunctionalTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The scoring controls the owner sees. The clamp itself is unit tested; this
 * covers that the page does not offer a button for something the handler will
 * refuse anyway.
 */
class ScoreControlsTest extends FunctionalTestCase
{
    public function testTheMinusButtonsAreDisabledAtNil(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'nil-vs-nil-2026-12-01');

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());

        $this->assertTrue($this->isDisabled($crawler, 'H−'));
        $this->assertTrue($this->isDisabled($crawler, 'A−'));
        $this->assertFalse($this->isDisabled($crawler, 'H+'));
    }

    public function testAMinusButtonComesBackWithTheFirstPoint(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'one-vs-nil-2026-12-01', homepoints: 1);

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());

        $this->assertFalse($this->isDisabled($crawler, 'H−'));
        $this->assertTrue($this->isDisabled($crawler, 'A−'));
    }

    public function testAVisitorGetsNoScoringControlsAtAll(): void
    {
        $game = $this->createGame($this->createUser('owner@example.com'), 'visitor-vs-controls-2026-12-01', homepoints: 2);

        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());

        $this->assertCount(0, $crawler->filter('button')->reduce(
            static fn (Crawler $button): bool => \in_array(trim($button->text()), ['H+', 'H−', 'A+', 'A−'], true),
        ));
    }

    private function isDisabled(Crawler $crawler, string $label): bool
    {
        $button = $crawler->filter('button')->reduce(
            static fn (Crawler $node): bool => trim($node->text()) === $label,
        );

        $this->assertCount(1, $button, sprintf('Expected exactly one "%s" button.', $label));

        // Not a substring check on the markup: the class list carries Tailwind's
        // disabled: variants and would match either way.
        return $button->attr('disabled') !== null;
    }
}

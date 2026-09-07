<?php

namespace App\Tests\Controller;

use App\Entity\Game;
use App\Tests\Support\FunctionalTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The sport's own buttons: one tap has to move the score and write the ticker
 * entry together, and it must not be possible to talk the route into awarding
 * points it does not own.
 */
class SportEventsTest extends FunctionalTestCase
{
    private array $written = [];

    protected function tearDown(): void
    {
        foreach ($this->written as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    public function testAThreePointerMovesTheScoreByThreeAndWritesTheEntry(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'korb-vs-dreier-2026-12-01', sport: 'basketball');

        $this->client->loginUser($owner);
        $this->client->request('POST', '/games/' . $game->getSlug() . '/record', [
            '_token' => $this->scoreToken($game),
            'timecode' => '12',
            'event' => 'dreier:home',
        ]);

        $this->assertResponseRedirects('/games/' . $game->getSlug());

        $game = $this->reload($game);
        $entry = $game->getGameEvents()->first();

        $this->assertSame(3, $game->getHomepoints());
        $this->assertSame(0, $game->getAwaypoints());
        $this->assertSame('dreier', $entry->getType());
        $this->assertSame('12', $entry->getTimecode());
        $this->assertSame('3er für Falcons', $entry->getMessage());
    }

    public function testAnEventWithoutPointsLeavesTheScoreAlone(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'karte-vs-rot-2026-12-01', sport: 'fussball', homepoints: 1);

        $this->client->loginUser($owner);
        $this->client->request('POST', '/games/' . $game->getSlug() . '/record', [
            '_token' => $this->scoreToken($game),
            'event' => 'rote-karte:away',
        ]);

        $game = $this->reload($game);

        $this->assertSame(1, $game->getHomepoints());
        $this->assertSame(0, $game->getAwaypoints());
        $this->assertSame('Rote Karte für Sharks', $game->getGameEvents()->first()->getMessage());
    }

    public function testAGameCannotBeAwardedPointsFromAnotherSport(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'fussball-vs-dreier-2026-12-01', sport: 'fussball');

        $this->client->loginUser($owner);
        $this->client->catchExceptions(false);

        // Otherwise a hand-written POST could pick whichever event pays best.
        $this->expectExceptionMessage('Unknown event "dreier" for sport "fussball"');

        $this->client->request('POST', '/games/' . $game->getSlug() . '/record', [
            '_token' => $this->scoreToken($game),
            'event' => 'dreier:home',
        ]);
    }

    public function testTheButtonsAreTheSportsOwnAndReplaceThePlusControls(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'buttons-vs-sport-2026-12-01', sport: 'basketball');

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());

        $this->assertNotEmpty($this->buttonValues($crawler, 'dreier:home'));
        $this->assertNotEmpty($this->buttonValues($crawler, 'freiwurf:away'));

        // The plus is the event grid now; the minus stays as the way back.
        $this->assertCount(0, $crawler->filter('button')->reduce(
            static fn (Crawler $node): bool => trim($node->text()) === 'H+',
        ));
        $this->assertCount(1, $crawler->filter('button')->reduce(
            static fn (Crawler $node): bool => trim($node->text()) === 'H−',
        ));
    }

    public function testAnOpenGameKeepsThePlainScoreboard(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'offen-vs-plain-2026-12-01');

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());

        $this->assertCount(1, $crawler->filter('button')->reduce(
            static fn (Crawler $node): bool => trim($node->text()) === 'H+',
        ));
        $this->assertCount(0, $crawler->filter('form[action$="/record"]'));
    }

    public function testTheSnapshotCarriesTheSportAndTheSymbols(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'symbol-vs-snapshot-2026-12-01', sport: 'fussball');
        $this->written[] = $snapshot = static::getContainer()->getParameter('kernel.cache_dir')
            . '/public/games/' . $game->getSlug() . '.json';

        $this->client->loginUser($owner);
        $this->client->request('POST', '/games/' . $game->getSlug() . '/record', [
            '_token' => $this->scoreToken($game),
            'event' => 'gelbe-karte:home',
        ]);

        $written = json_decode(file_get_contents($snapshot), true);

        $this->assertSame('fussball', $written['sport']);
        $this->assertSame('🟨', $written['events'][0]['icon']);
    }

    /**
     * The token belongs to the session, so it has to come off a page this
     * browser was served.
     */
    private function scoreToken(Game $game): string
    {
        return $this->client->request('GET', '/games/' . $game->getSlug())
            ->filter('form[action$="/record"] input[name="_token"], form[action*="decreasehome"] input[name="_token"]')
            ->first()
            ->attr('value');
    }

    private function buttonValues(Crawler $crawler, string $value): array
    {
        return $crawler->filter(sprintf('button[name="event"][value="%s"]', $value))->extract(['value']);
    }

    private function reload(Game $game): Game
    {
        return $this->entityManager->find(Game::class, $game->getId());
    }
}

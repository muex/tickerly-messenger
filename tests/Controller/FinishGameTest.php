<?php

namespace App\Tests\Controller;

use App\Entity\Game;
use App\Tests\Support\FunctionalTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The final whistle. What matters is that it actually closes the game — the
 * controls disappearing is cosmetic, the voter refusing the routes is not —
 * and that it can be taken back.
 */
class FinishGameTest extends FunctionalTestCase
{
    public function testTheOwnerCanEndAGameAndTheScoreStops(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'whistle-vs-blown-2026-12-01', homepoints: 2, awaypoints: 1);

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());
        $this->client->submit($crawler->selectButton('Spiel beenden')->form());

        $this->assertResponseRedirects('/games/' . $game->getSlug());
        $crawler = $this->client->followRedirect();

        $this->assertTrue($this->reload($game)->isFinished());
        $this->assertStringContainsString('Endstand', $crawler->filter('#final')->text());
        $this->assertCount(0, $this->button($crawler, 'H+'));
        $this->assertCount(0, $crawler->filter('form[action$="/events"]'));
    }

    public function testAFinishedGameRefusesPointsEvenFromItsOwner(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'closed-vs-shut-2026-12-01', finishedAt: new \DateTimeImmutable());
        $running = $this->createGame($owner, 'still-vs-running-2026-12-01');

        $this->client->loginUser($owner);

        // A tab opened before the whistle still has a valid token and a live
        // H+ button. Pressing it must fail on the voter, not just on the markup
        // that no longer offers it.
        $stale = $this->client->request('GET', '/games/' . $running->getSlug())
            ->selectButton('H+')
            ->form();

        $this->client->request('POST', '/games/' . $game->getSlug() . '/increasehome', $stale->getValues());

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(0, $this->reload($game)->getHomepoints());
    }

    public function testAFinishedGameRefusesTickerEntries(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'silent-vs-ticker-2026-12-01', finishedAt: new \DateTimeImmutable());

        $this->client->loginUser($owner);
        $this->client->request('POST', '/games/' . $game->getSlug() . '/events');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testTheWhistleCanBeTakenBack(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'reopen-vs-resume-2026-12-01', finishedAt: new \DateTimeImmutable());

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());
        $this->client->submit($crawler->selectButton('Spiel wieder öffnen')->form());

        $crawler = $this->client->followRedirect();

        $this->assertFalse($this->reload($game)->isFinished());
        $this->assertCount(1, $this->button($crawler, 'H+'));
        $this->assertCount(1, $crawler->filter('form[action$="/events"]'));
    }

    public function testNobodyElseCanEndSomeoneElsesGame(): void
    {
        $game = $this->createGame($this->createUser('owner@example.com'), 'stranger-vs-whistle-2026-12-01');

        // The token belongs to the session, so it has to come from a page this
        // browser was actually served: the stranger's own game.
        $this->client->loginUser($stranger = $this->createUser('someone@example.com'));
        $own = $this->createGame($stranger, 'stranger-vs-own-2026-12-01');

        $form = $this->client->request('GET', '/games/' . $own->getSlug())
            ->selectButton('Spiel beenden')
            ->form();

        $this->client->request('POST', '/games/' . $game->getSlug() . '/finish', $form->getValues());

        $this->assertResponseStatusCodeSame(403);
        $this->assertFalse($this->reload($game)->isFinished());
    }

    public function testASpectatorSeesTheFinalScoreAndNothingToPoll(): void
    {
        $game = $this->createGame(
            $this->createUser('owner@example.com'),
            'final-vs-spectator-2026-12-01',
            finishedAt: new \DateTimeImmutable(),
        );

        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());

        $this->assertCount(1, $crawler->filter('#final:not([hidden])'));

        // A score that cannot change is not worth a request a minute.
        $this->assertCount(0, $crawler->filter('#autorefresh-toggle'));
    }

    /**
     * The request detaches the entities the test made, so state has to be read
     * back from the database rather than off the stale object.
     */
    private function reload(Game $game): Game
    {
        return $this->entityManager->find(Game::class, $game->getId());
    }

    private function button(Crawler $crawler, string $label): Crawler
    {
        return $crawler->filter('button')->reduce(
            static fn (Crawler $node): bool => trim($node->text()) === $label,
        );
    }
}

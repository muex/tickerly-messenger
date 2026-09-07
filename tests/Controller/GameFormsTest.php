<?php

namespace App\Tests\Controller;

use App\Entity\Game;
use App\Tests\Support\FunctionalTestCase;

/**
 * The two form-driven write paths. They go through a plain data object rather
 * than the entity, so that the command handler is the only thing that writes —
 * which is exactly what nothing covered before.
 */
class GameFormsTest extends FunctionalTestCase
{
    public function testCreatingAGameStoresItForTheUserWhoFilledTheForm(): void
    {
        $owner = $this->createUser('owner@example.com');
        $this->client->loginUser($owner);

        $crawler = $this->client->request('GET', '/games/new');
        $this->client->submit($crawler->selectButton('Speichern')->form([
            'game[home]' => 'Wölfe',
            'game[away]' => 'Bären',
            'game[location]' => 'Waldstadion',
            'game[datetime]' => '2026-12-24T18:00',
            'game[sport]' => 'handball',
        ]));

        $game = $this->findBySlug('woelfe-vs-baeren-2026-12-24');

        $this->assertNotNull($game, 'The game was not created.');
        $this->assertSame('Wölfe', $game->getHome());
        $this->assertSame('Waldstadion', $game->getLocation());
        $this->assertSame('handball', $game->getSport());
        $this->assertSame($owner->getId()->toRfc4122(), $game->getOwner()->getId()->toRfc4122());
    }

    public function testTheOwnerCanCorrectATypoInTheTeamName(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'typo-vs-fixed-2026-12-01');

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/games/' . $game->getSlug() . '/edit');
        $this->client->submit($crawler->selectButton('Aktualisieren')->form([
            'game[home]' => 'Falken',
        ]));

        $this->assertSame('Falken', $this->reload($game)->getHome());
    }

    public function testTheSportCanStillBeChangedAfterwards(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'wrong-vs-sport-2026-12-01');

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/games/' . $game->getSlug() . '/edit');
        $this->client->submit($crawler->selectButton('Aktualisieren')->form([
            'game[sport]' => 'volleyball',
        ]));

        // Picked the wrong one when creating the game, or picked it before
        // knowing: either way it must not be frozen.
        $this->assertSame('volleyball', $this->reload($game)->getSport());
    }

    public function testASubmissionThatFailsValidationChangesNothing(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'invalid-vs-untouched-2026-12-01');

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/games/' . $game->getSlug() . '/edit');

        // A forged token: the form is handled, but the command never leaves the
        // controller. Nothing may have been written on the way.
        $this->client->submit($crawler->selectButton('Aktualisieren')->form([
            'game[home]' => 'Falken',
            'game[_token]' => 'not-the-token',
        ]));

        $this->assertSame('Falcons', $this->reload($game)->getHome());
    }

    private function reload(Game $game): Game
    {
        return $this->entityManager->find(Game::class, $game->getId());
    }

    private function findBySlug(string $slug): ?Game
    {
        return $this->entityManager->getRepository(Game::class)->findOneBy(['slug' => $slug]);
    }
}

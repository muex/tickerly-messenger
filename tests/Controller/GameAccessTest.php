<?php

namespace App\Tests\Controller;

use App\Tests\Support\FunctionalTestCase;

/**
 * The ownership rules the GameVoter promises, over real HTTP: the unit test
 * covers the voter's decision, this covers that every mutating route actually
 * asks it.
 */
class GameAccessTest extends FunctionalTestCase
{
    public function testAStrangerCannotEditSomeoneElsesGame(): void
    {
        $game = $this->createGame($this->createUser('owner@example.com'), 'owner-vs-stranger-2026-12-01');

        $this->client->loginUser($this->createUser('stranger@example.com'));
        $this->client->request('GET', '/games/' . $game->getSlug() . '/edit');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testTheOwnerCanEditTheirGame(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'owner-vs-owner-2026-12-01');

        $this->client->loginUser($owner);
        $this->client->request('GET', '/games/' . $game->getSlug() . '/edit');

        $this->assertResponseIsSuccessful();
    }

    public function testAStrangerCannotPostEventsToSomeoneElsesGame(): void
    {
        $game = $this->createGame($this->createUser('owner@example.com'), 'events-vs-stranger-2026-12-01');

        $this->client->loginUser($this->createUser('stranger@example.com'));
        $this->client->request('POST', '/games/' . $game->getSlug() . '/events');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testScoringWithoutACsrfTokenIsRejected(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'csrf-vs-owner-2026-12-01');

        $this->client->loginUser($owner);
        // Even for the owner: a score must not move on a forged cross-site POST.
        $this->client->request('POST', '/games/' . $game->getSlug() . '/increasehome');

        // A missing token raises InvalidCsrfTokenException, which the firewall
        // answers with its entry point rather than a bare 403.
        $this->assertResponseRedirects('/login');
        $this->assertSame(0, $game->getHomepoints());
    }

    public function testTheAdminAreaIsClosedToOrdinaryUsers(): void
    {
        $this->client->loginUser($this->createUser('user@example.com'));
        $this->client->request('GET', '/admin');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAnAdminReachesTheAdminArea(): void
    {
        $this->client->loginUser($this->createUser('admin@example.com', ['ROLE_ADMIN']));
        $this->client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
    }
}

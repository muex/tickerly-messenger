<?php

namespace App\Tests\Controller;

use App\Tests\Support\FunctionalTestCase;

/**
 * The path a score takes to a spectator: the owner clicks, the projector writes
 * the snapshot, and the page carries the switch that polls it. Nothing here
 * touches the real public/ directory — under test the projector writes into the
 * cache directory (see config/services.yaml).
 */
class LiveSnapshotTest extends FunctionalTestCase
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

    public function testAScoredPointLandsInTheSnapshotTheSpectatorPolls(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'snapshot-vs-ticker-2026-12-01');
        $this->written[] = $snapshot = $this->pathFor($game->getSlug());

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());
        $this->client->submit($crawler->selectButton('H+')->form());

        $this->assertFileExists($snapshot);

        $written = json_decode(file_get_contents($snapshot), true);

        $this->assertSame(1, $written['homepoints']);
        $this->assertSame(0, $written['awaypoints']);
        $this->assertSame([], $written['events']);
    }

    public function testANewTickerEntryLandsInTheSnapshotToo(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'ticker-vs-entry-2026-12-01');
        $this->written[] = $snapshot = $this->pathFor($game->getSlug());

        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());

        $form = $crawler->selectButton('Speichern')->form([
            'game_event[timecode]' => '67',
            'game_event[message]' => 'Gelbe Karte',
        ]);
        $this->client->submit($form);

        $written = json_decode(file_get_contents($snapshot), true);

        $this->assertSame([['timecode' => '67', 'message' => 'Gelbe Karte']], $written['events']);
    }

    public function testOnlySpectatorsGetTheSwitchThatPolls(): void
    {
        $owner = $this->createUser('owner@example.com');
        $game = $this->createGame($owner, 'spectator-vs-owner-2026-12-01');

        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());
        $toggle = $crawler->filter('#autorefresh-toggle');

        $this->assertCount(1, $toggle);
        $this->assertSame('/games/' . $game->getSlug() . '.json', $toggle->attr('data-snapshot'));

        // The owner is the one typing into the event form; nothing may reload or
        // rewrite the page under them.
        $this->client->loginUser($owner);
        $crawler = $this->client->request('GET', '/games/' . $game->getSlug());

        $this->assertCount(0, $crawler->filter('#autorefresh-toggle'));
    }

    private function pathFor(string $slug): string
    {
        return static::getContainer()->getParameter('kernel.cache_dir') . '/public/games/' . $slug . '.json';
    }
}

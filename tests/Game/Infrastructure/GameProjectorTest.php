<?php

namespace App\Tests\Game\Infrastructure;

use App\Entity\Game;
use App\Entity\GameEvent;
use App\Game\Infrastructure\GameProjector;
use App\Repository\GameRepository;
use PHPUnit\Framework\TestCase;

/**
 * The per-game snapshots the ticker page polls. They live in the web root, so
 * what matters is both that they appear with the right content and that they
 * disappear the moment a game stops being public.
 */
class GameProjectorTest extends TestCase
{
    private string $webRoot;

    protected function setUp(): void
    {
        $this->webRoot = sys_get_temp_dir() . '/tickerly-projector-' . bin2hex(random_bytes(4));
        mkdir($this->webRoot);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->webRoot . '/*/*') ?: [] as $file) {
            unlink($file);
        }

        foreach (glob($this->webRoot . '/*') ?: [] as $path) {
            is_dir($path) ? rmdir($path) : unlink($path);
        }

        rmdir($this->webRoot);
    }

    public function testItWritesTheSnapshotTheTickerPagePolls(): void
    {
        $game = $this->game();
        $game->setHomepoints(2);
        $game->addGameEvent((new GameEvent())->setTimecode('12')->setMessage('Tor für die Falcons'));

        $this->projector($game)->projectReadModels('falcons-vs-sharks-2026-12-01');

        $snapshot = $this->snapshot('falcons-vs-sharks-2026-12-01');

        $this->assertSame(2, $snapshot['homepoints']);
        $this->assertSame(0, $snapshot['awaypoints']);
        $this->assertSame('Falcons', $snapshot['home']);
        $this->assertSame([['timecode' => '12', 'message' => 'Tor für die Falcons']], $snapshot['events']);
    }

    public function testADeactivatedGameLosesItsSnapshot(): void
    {
        $game = $this->game();
        $projector = $this->projector($game);

        $projector->projectReadModels('falcons-vs-sharks-2026-12-01');
        $game->setActive(false);
        $projector->projectReadModels('falcons-vs-sharks-2026-12-01');

        // A file in the web root is served before any voter runs, so leaving it
        // behind would keep a hidden game readable.
        $this->assertFileDoesNotExist($this->pathFor('falcons-vs-sharks-2026-12-01'));
    }

    public function testADeletedGameLosesItsSnapshot(): void
    {
        $projector = $this->projector($this->game());
        $projector->projectReadModels('falcons-vs-sharks-2026-12-01');

        // The row is gone by the time the projector looks it up.
        $this->projector(null)->projectReadModels('falcons-vs-sharks-2026-12-01');

        $this->assertFileDoesNotExist($this->pathFor('falcons-vs-sharks-2026-12-01'));
    }

    public function testARebuildDropsSnapshotsThatNoLongerBelongToAGame(): void
    {
        mkdir($this->webRoot . '/games');
        file_put_contents($this->pathFor('gone-vs-forgotten-2020-01-01'), '{}');

        $this->projector($this->game())->projectAll();

        $this->assertFileExists($this->pathFor('falcons-vs-sharks-2026-12-01'));
        $this->assertFileDoesNotExist($this->pathFor('gone-vs-forgotten-2020-01-01'));
    }

    private function projector(?Game $game): GameProjector
    {
        $repository = $this->createStub(GameRepository::class);
        $repository->method('findNextGames')->willReturn([]);
        $repository->method('findLastGames')->willReturn([]);
        $repository->method('findOneBy')->willReturn($game);
        $repository->method('findActive')->willReturn($game !== null && $game->isActive() ? [$game] : []);

        return new GameProjector($repository, $this->webRoot);
    }

    private function game(): Game
    {
        return (new Game())
            ->setHome('Falcons')
            ->setAway('Sharks')
            ->setLocation('Stadthalle')
            ->setDatetime(new \DateTime('2026-12-01 19:30'))
            ->setSlug('falcons-vs-sharks-2026-12-01')
            ->setActive(true);
    }

    private function pathFor(string $slug): string
    {
        return $this->webRoot . '/games/' . $slug . '.json';
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(string $slug): array
    {
        return json_decode(file_get_contents($this->pathFor($slug)), true);
    }
}

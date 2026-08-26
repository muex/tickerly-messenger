<?php

namespace App\Tests\Game\Application\Command;

use App\Entity\Game;
use App\Game\Application\Command\DecreaseAwayPoints;
use App\Game\Application\Command\DecreaseAwayPointsHandler;
use App\Game\Application\Command\DecreaseHomePoints;
use App\Game\Application\Command\DecreaseHomePointsHandler;
use App\Game\Application\Event\GameStateChanged;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class DecreasePointsHandlerTest extends TestCase
{
    public function testItTakesAPointOffTheHomeSide(): void
    {
        $game = (new Game())->setHomepoints(5);
        $gameId = Uuid::v7();

        (new DecreaseHomePointsHandler(...$this->collaborators($gameId, $game)))(new DecreaseHomePoints($gameId));

        $this->assertSame(4, $game->getHomepoints());
    }

    public function testItStopsAtNilForTheHomeSide(): void
    {
        $game = (new Game())->setHomepoints(0);
        $gameId = Uuid::v7();

        (new DecreaseHomePointsHandler(...$this->collaborators($gameId, $game)))(new DecreaseHomePoints($gameId));

        // A stray tap on the minus button must not invent a negative score.
        $this->assertSame(0, $game->getHomepoints());
    }

    public function testItStopsAtNilForTheAwaySide(): void
    {
        $game = (new Game())->setAwaypoints(0);
        $gameId = Uuid::v7();

        (new DecreaseAwayPointsHandler(...$this->collaborators($gameId, $game)))(new DecreaseAwayPoints($gameId));

        $this->assertSame(0, $game->getAwaypoints());
    }

    /**
     * @return array{GameRepository, EventBus}
     */
    private function collaborators(Uuid $gameId, Game $game): array
    {
        $repository = $this->createMock(GameRepository::class);
        $repository->expects($this->once())->method('find')->with($gameId)->willReturn($game);
        $repository->expects($this->once())->method('save')->with($game, true);

        $eventBus = $this->createMock(EventBus::class);
        $eventBus->expects($this->once())->method('dispatch')->with($this->isInstanceOf(GameStateChanged::class));

        return [$repository, $eventBus];
    }
}

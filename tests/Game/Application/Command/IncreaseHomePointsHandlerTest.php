<?php

namespace App\Tests\Game\Application\Command;

use App\Entity\Game;
use App\Game\Application\Command\IncreaseHomePoints;
use App\Game\Application\Command\IncreaseHomePointsHandler;
use App\Game\Application\Event\GameStateChanged;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use PHPUnit\Framework\TestCase;

class IncreaseHomePointsHandlerTest extends TestCase
{
    public function testItIncrementsHomePointsPersistsAndSignalsChange(): void
    {
        $game = (new Game())->setHomepoints(5);

        $repository = $this->createMock(GameRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn($game);
        $repository->expects($this->once())
            ->method('save')
            ->with($game, true);

        $eventBus = $this->createMock(EventBus::class);
        $eventBus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(GameStateChanged::class));

        $handler = new IncreaseHomePointsHandler($repository, $eventBus);
        $handler(new IncreaseHomePoints(42));

        $this->assertSame(6, $game->getHomepoints());
    }
}

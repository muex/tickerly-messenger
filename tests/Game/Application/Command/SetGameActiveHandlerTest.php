<?php

namespace App\Tests\Game\Application\Command;

use App\Entity\Game;
use App\Game\Application\Command\SetGameActive;
use App\Game\Application\Command\SetGameActiveHandler;
use App\Game\Application\Event\GameStateChanged;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class SetGameActiveHandlerTest extends TestCase
{
    public function testItDeactivatesTheGamePersistsAndSignalsChange(): void
    {
        $game = new Game();
        $gameId = Uuid::v7();

        $repository = $this->createMock(GameRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with($gameId)
            ->willReturn($game);
        $repository->expects($this->once())
            ->method('save')
            ->with($game, true);

        $eventBus = $this->createMock(EventBus::class);
        // The read models must be rebuilt so the game leaves the public lists.
        $eventBus->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(GameStateChanged::class));

        $handler = new SetGameActiveHandler($repository, $eventBus);
        $handler(new SetGameActive($gameId, false));

        $this->assertFalse($game->isActive());
    }
}

<?php

namespace App\Game\Application\Command;

use App\Entity\Game;
use App\Game\Application\Event\GameStateChanged;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class CreateGameHandler
{
    public function __construct(private GameRepository $gameRepository, private EventBus $eventBus){
    }

    public function __invoke(CreateGame $game)
    {
        $newGame = new Game();
        $newGame->setOwner($game->getOwner());
        $newGame->setHome($game->getHome());
        $newGame->setAway($game->getAway());
        $newGame->setLocation($game->getLocation());
        $newGame->setDatetime($game->getDatetime());

        $this->gameRepository->save($newGame, true);

        $this->eventBus->dispatch(new GameStateChanged());
    }
}
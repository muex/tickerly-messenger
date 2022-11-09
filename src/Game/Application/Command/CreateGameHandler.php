<?php

namespace App\Game\Application\Command;

use App\Entity\Game;
use App\Game\Application\Event\GameCreated;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CreateGameHandler implements MessageHandlerInterface
{
    public function __construct(GameRepository $gameRepository, EventBus $eventBus){
        $this->gameRepository = $gameRepository;
        $this->eventBus = $eventBus;
    }

    public function __invoke(CreateGame $game)
    {
        $newGame = new Game();
        $newGame->setOwner($game->getOwner());
        $newGame->setHome($game->getHome());
        $newGame->setAway($game->getAway());
        $newGame->setLocation($game->getLocation());
        $newGame->setDatetime($game->getDatetime());
        $newGame->setOwner($game->getOwner());

        $this->gameRepository->save($newGame, true);

        $this->eventBus->dispatch(new GameCreated());
    }
}
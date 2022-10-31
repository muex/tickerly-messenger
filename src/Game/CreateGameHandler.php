<?php

namespace App\Game;

use App\Entity\Game;
use App\Repository\GameRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class CreateGameHandler implements MessageHandlerInterface
{
    public function __construct(GameRepository $gameRepository){
        $this->gameRepository = $gameRepository;
    }

    public function __invoke(CreateGame $game, MessageBusInterface $eventBus)
    {
        $newGame = new Game();
        $newGame->setUser($game->getUser());
        $newGame->setHome($game->getHome());
        $newGame->setAway($game->getAway());
        $newGame->setPlace($game->getPlace());
        $newGame->setDatetime($game->getDatetime());
        $newGame->setUser($game->getUser());

        $this->gameRepository->save($newGame, true);

        // Dispatch an event message on an event bus
        // create json file with uuid and update gamelist json
        //$this->eventBus->dispatch(new GameCreatedEvent($newGame));
    }
}
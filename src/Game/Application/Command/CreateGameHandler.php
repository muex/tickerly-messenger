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
        $newGame->setUser($game->getUser());
        $newGame->setHome($game->getHome());
        $newGame->setAway($game->getAway());
        $newGame->setPlace($game->getPlace());
        $newGame->setDatetime($game->getDatetime());
        $newGame->setUser($game->getUser());

        $this->gameRepository->save($newGame, true);

        // Dispatch an event message on an event bus
        // create json file with uuid and update gamelist json
        $this->eventBus->dispatch(new GameCreated());
    }
}
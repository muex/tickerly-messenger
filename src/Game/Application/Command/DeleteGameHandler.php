<?php

namespace App\Game\Application\Command;

use App\Repository\GameRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class DeleteGameHandler implements MessageHandlerInterface
{
    public function __construct(GameRepository $gameRepository){
        $this->gameRepository = $gameRepository;
    }

    public function __invoke(DeleteGame $deleteGame)
    {
        $game = $this->gameRepository->find($deleteGame->getGameId());
        $this->gameRepository->remove($game, true);

    }
}
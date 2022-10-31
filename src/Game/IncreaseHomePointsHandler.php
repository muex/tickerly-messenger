<?php

namespace App\Game;

use App\Repository\GameRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class IncreaseHomePointsHandler implements MessageHandlerInterface
{
    public function __construct(GameRepository $gameRepository){
        $this->gameRepository = $gameRepository;
    }

    public function __invoke(IncreaseHomePoints $increaseHomePoints)
    {
        $game = $this->gameRepository->find($increaseHomePoints->getGameId());
        $points=$game->getHomepoints();
        $game->setHomepoints(++$points);
        $this->gameRepository->save($game, true);
    }
}
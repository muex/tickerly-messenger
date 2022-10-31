<?php

namespace App\Game\Application\Command;

use App\Repository\GameRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class DecreaseHomePointsHandler implements MessageHandlerInterface
{
    public function __construct(GameRepository $gameRepository){
        $this->gameRepository = $gameRepository;
    }

    public function __invoke(DecreaseHomePoints $decreaseHomePoints)
    {
        $game = $this->gameRepository->find($decreaseHomePoints->getGameId());
        $points=$game->getHomepoints();
        $game->setHomepoints(--$points);
        $this->gameRepository->save($game, true);
    }
}
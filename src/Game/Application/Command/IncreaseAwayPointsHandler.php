<?php

namespace App\Game\Application\Command;

use App\Repository\GameRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class IncreaseAwayPointsHandler implements MessageHandlerInterface
{
    public function __construct(GameRepository $gameRepository){
        $this->gameRepository = $gameRepository;
    }

    public function __invoke(IncreaseAwayPoints $increaseAwayPoints)
    {
        $game = $this->gameRepository->find($increaseAwayPoints->getGameId());
        $points=$game->getAwaypoints();
        $game->setAwaypoints(++$points);
        $this->gameRepository->save($game, true);
    }
}
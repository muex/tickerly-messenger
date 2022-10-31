<?php

namespace App\Game\Application\Command;

use App\Repository\GameRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class DecreaseAwayPointsHandler implements MessageHandlerInterface
{
    public function __construct(GameRepository $gameRepository){
        $this->gameRepository = $gameRepository;
    }

    public function __invoke(DecreaseAwayPoints $decreaseAwayPoints)
    {
        $game = $this->gameRepository->find($decreaseAwayPoints->getGameId());
        $points=$game->getAwaypoints();
        $game->setAwaypoints(--$points);
        $this->gameRepository->save($game, true);
    }
}
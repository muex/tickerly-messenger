<?php

namespace App\Game\Application\Command;

use App\Game\Infrastructure\GameProjector;
use App\Repository\GameRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class IncreaseAwayPointsHandler implements MessageHandlerInterface
{
    public function __construct(private GameRepository $gameRepository, private GameProjector $gameProjector) {}

    public function __invoke(IncreaseAwayPoints $increaseAwayPoints)
    {
        $game = $this->gameRepository->find($increaseAwayPoints->getGameId());
        $points=$game->getAwaypoints();
        $game->setAwaypoints(++$points);
        $this->gameRepository->save($game, true);

        $this->gameProjector->projectReadModels();
    }
}

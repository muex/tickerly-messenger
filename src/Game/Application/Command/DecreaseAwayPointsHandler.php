<?php

namespace App\Game\Application\Command;

use App\Game\Infrastructure\GameProjector;
use App\Repository\GameRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class DecreaseAwayPointsHandler implements MessageHandlerInterface
{
    public function __construct(private GameRepository $gameRepository, private GameProjector $gameProjector) {}

    public function __invoke(DecreaseAwayPoints $decreaseAwayPoints)
    {
        $game = $this->gameRepository->find($decreaseAwayPoints->getGameId());
        $points=$game->getAwaypoints();
        $game->setAwaypoints(--$points);
        $this->gameRepository->save($game, true);

        $this->gameProjector->projectReadModels();
    }
}

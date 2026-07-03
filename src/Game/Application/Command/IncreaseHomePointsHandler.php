<?php

namespace App\Game\Application\Command;

use App\Game\Infrastructure\GameProjector;
use App\Repository\GameRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class IncreaseHomePointsHandler implements MessageHandlerInterface
{
    public function __construct(private GameRepository $gameRepository, private GameProjector $gameProjector) {}

    public function __invoke(IncreaseHomePoints $increaseHomePoints)
    {
        $game = $this->gameRepository->find($increaseHomePoints->getGameId());
        $points=$game->getHomepoints();
        $game->setHomepoints(++$points);
        $this->gameRepository->save($game, true);

        $this->gameProjector->projectReadModels();
    }
}

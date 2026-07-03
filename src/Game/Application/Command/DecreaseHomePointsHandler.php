<?php

namespace App\Game\Application\Command;

use App\Game\Infrastructure\GameProjector;
use App\Repository\GameRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class DecreaseHomePointsHandler implements MessageHandlerInterface
{
    public function __construct(private GameRepository $gameRepository, private GameProjector $gameProjector) {}

    public function __invoke(DecreaseHomePoints $decreaseHomePoints)
    {
        $game = $this->gameRepository->find($decreaseHomePoints->getGameId());
        $points=$game->getHomepoints();
        $game->setHomepoints(--$points);
        $this->gameRepository->save($game, true);

        $this->gameProjector->projectReadModels();
    }
}

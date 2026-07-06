<?php

namespace App\Game\Application\Command;

use App\Game\Application\Event\GameStateChanged;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class IncreaseHomePointsHandler
{
    public function __construct(private GameRepository $gameRepository, private EventBus $eventBus) {}

    public function __invoke(IncreaseHomePoints $increaseHomePoints)
    {
        $game = $this->gameRepository->find($increaseHomePoints->getGameId());
        $points=$game->getHomepoints();
        $game->setHomepoints(++$points);
        $this->gameRepository->save($game, true);

        $this->eventBus->dispatch(new GameStateChanged());
    }
}

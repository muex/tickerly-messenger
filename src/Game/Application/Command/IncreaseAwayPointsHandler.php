<?php

namespace App\Game\Application\Command;

use App\Game\Application\Event\GameStateChanged;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class IncreaseAwayPointsHandler
{
    public function __construct(private GameRepository $gameRepository, private EventBus $eventBus) {}

    public function __invoke(IncreaseAwayPoints $increaseAwayPoints)
    {
        $game = $this->gameRepository->find($increaseAwayPoints->getGameId());
        $points=$game->getAwaypoints();
        $game->setAwaypoints(++$points);
        $this->gameRepository->save($game, true);

        $this->eventBus->dispatch(new GameStateChanged());
    }
}

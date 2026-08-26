<?php

namespace App\Game\Application\Command;

use App\Game\Application\Event\GameStateChanged;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class DecreaseHomePointsHandler
{
    public function __construct(private GameRepository $gameRepository, private EventBus $eventBus) {}

    public function __invoke(DecreaseHomePoints $decreaseHomePoints)
    {
        $game = $this->gameRepository->find($decreaseHomePoints->getGameId());
        // No sport this app tickers has a score below zero: a stray tap on the
        // minus button must not push the game into negative numbers.
        $game->setHomepoints(max(0, $game->getHomepoints() - 1));
        $this->gameRepository->save($game, true);

        $this->eventBus->dispatch(new GameStateChanged());
    }
}

<?php

namespace App\Game\Application\Command;

use App\Game\Application\Event\GameStateChanged;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class UpdateGameHandler
{
    public function __construct(private GameRepository $gameRepository, private EventBus $eventBus) {}

    public function __invoke(UpdateGame $updateGame)
    {
        $game = $this->gameRepository->find($updateGame->getGameId());
        $game->setHome($updateGame->getHome());
        $game->setAway($updateGame->getAway());
        $game->setLocation($updateGame->getLocation());
        $game->setDatetime($updateGame->getDatetime());

        $this->gameRepository->save($game, true);

        $this->eventBus->dispatch(new GameStateChanged($game->getSlug()));
    }
}

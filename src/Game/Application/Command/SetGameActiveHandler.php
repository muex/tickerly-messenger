<?php

namespace App\Game\Application\Command;

use App\Game\Application\Event\GameStateChanged;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class SetGameActiveHandler
{
    public function __construct(private GameRepository $gameRepository, private EventBus $eventBus) {}

    public function __invoke(SetGameActive $setGameActive): void
    {
        $game = $this->gameRepository->find($setGameActive->getGameId());
        $game->setActive($setGameActive->isActive());
        $this->gameRepository->save($game, true);

        // Rebuilds the read models so a deactivated game leaves the public lists.
        $this->eventBus->dispatch(new GameStateChanged($game->getSlug()));
    }
}

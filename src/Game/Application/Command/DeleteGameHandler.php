<?php

namespace App\Game\Application\Command;

use App\Game\Application\Event\GameStateChanged;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class DeleteGameHandler
{
    public function __construct(private GameRepository $gameRepository, private EventBus $eventBus) {}

    public function __invoke(DeleteGame $deleteGame)
    {
        $game = $this->gameRepository->find($deleteGame->getGameId());

        // Read before the row goes: the projector needs it to find the snapshot
        // file that has to disappear along with the game.
        $slug = $game->getSlug();

        $this->gameRepository->remove($game, true);

        $this->eventBus->dispatch(new GameStateChanged($slug));
    }
}

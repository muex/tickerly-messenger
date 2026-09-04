<?php

namespace App\Game\Application\Command;

use App\Entity\GameEvent;
use App\Game\Application\Event\GameStateChanged;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateGameEventHandler
{
    public function __construct(
        private GameRepository $gameRepository,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(CreateGameEvent $gameevent)
    {
        $game = $this->gameRepository->find($gameevent->getGameId());

        if ($game === null) {
            throw new \RuntimeException('Cannot add an entry to a game that no longer exists.');
        }

        $newGameevent = new GameEvent();
        $newGameevent->setTimecode($gameevent->getTimecode());
        $newGameevent->setMessage($gameevent->getMessage());

        // The association cascades persist, so saving the game saves the entry.
        $game->addGameEvent($newGameevent);
        $this->gameRepository->save($game, true);

        // The ticker itself is part of the game's snapshot, so a new entry makes
        // it stale just like a goal does.
        $this->eventBus->dispatch(new GameStateChanged($game->getSlug()));
    }
}

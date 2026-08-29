<?php

namespace App\Game\Application\Command;

use App\Entity\GameEvent;
use App\Game\Application\Event\GameStateChanged;
use App\Shared\Domain\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateGameEventHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(CreateGameEvent $gameevent)
    {
        $newGameevent = new GameEvent();
        $newGameevent->setGame($gameevent->getGame());
        $newGameevent->setTimecode($gameevent->getTimecode());
        $newGameevent->setMessage($gameevent->getMessage());
        $game = $gameevent->getGame();
        $game->addGameEvent($newGameevent);

        $this->entityManager->persist($newGameevent);
        $this->entityManager->flush();

        // The ticker itself is part of the game's snapshot, so a new entry makes
        // it stale just like a goal does.
        $this->eventBus->dispatch(new GameStateChanged($game->getSlug()));
    }
}

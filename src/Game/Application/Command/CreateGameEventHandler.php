<?php

namespace App\Game\Application\Command;

use App\Entity\Game;
use App\Entity\GameEvent;
use App\Game\Application\Event\GameCreated;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final class CreateGameEventHandler
{
    public function __construct(private GameRepository $gameRepository, private EventBus $eventBus, private EntityManagerInterface $entityManager){
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
        //$this->eventBus->dispatch(new GameEventCreated());
    }
}
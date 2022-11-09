<?php

namespace App\Game\Application\Command;

use App\Entity\Game;
use App\Entity\GameEvent;
use App\Game\Application\Event\GameCreated;
use App\Repository\GameRepository;
use App\Shared\Domain\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class CreateGameEventHandler implements MessageHandlerInterface
{
    public function __construct(GameRepository $gameRepository, EventBus $eventBus, EntityManagerInterface $entityManager){
        $this->gameRepository = $gameRepository;
        $this->eventBus = $eventBus;
        $this->entityManager = $entityManager;
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
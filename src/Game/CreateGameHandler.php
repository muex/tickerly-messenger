<?php

namespace App\Game;

use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CreateGameHandler implements MessageHandlerInterface
{
    public function __construct(private MessageHandlerInterface $eventBus)
    {
    }


    public function __invoke(CreateGame $game)
    {
        // Dispatch an event message on an event bus
        // create json file with uuid and update gamelist json
        $this->eventBus->dispatch(new GameCreatedEvent($gameId));
    }
}
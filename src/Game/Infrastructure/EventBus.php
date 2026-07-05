<?php

declare(strict_types=1);

namespace App\Game\Infrastructure;

use App\Shared\Domain\EventBus as GameEventBus;
use App\Shared\Domain\Event;
use Symfony\Component\Messenger\MessageBusInterface;

class EventBus implements GameEventBus
{
    public function __construct(private MessageBusInterface $eventBus) {}

    public function dispatch(Event $event): void
    {
        $this->eventBus->dispatch($event);
    }
}
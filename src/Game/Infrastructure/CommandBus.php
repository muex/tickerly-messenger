<?php

declare(strict_types=1);

namespace App\Game\Infrastructure;

use App\Shared\Domain\Command;
use App\Shared\Domain\CommandBus as GameCommandBus;
use Symfony\Component\Messenger\MessageBusInterface;

class CommandBus implements GameCommandBus
{
    public function __construct(private MessageBusInterface $commandMessageBus) {}

    public function dispatch(Command $command): void
    {
        $this->commandMessageBus->dispatch($command);
    }
}
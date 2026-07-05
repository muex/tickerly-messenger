<?php

declare(strict_types=1);

namespace App\Game\Infrastructure;

use App\Shared\Domain\Query;
use App\Shared\Domain\QueryBus as GameQueryBus;
use Symfony\Component\Messenger\MessageBusInterface;

class QueryBus implements GameQueryBus
{
    public function __construct(private MessageBusInterface $queryBus) {}

    public function dispatch(Query $query): void
    {
        $this->queryBus->dispatch($query);
    }
}
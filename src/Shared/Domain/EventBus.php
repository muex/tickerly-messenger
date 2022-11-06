<?php

declare(strict_types=1);

namespace App\Shared\Domain;

interface EventBus
{
    public function dispatch(Event $event): void;
}
<?php

declare(strict_types=1);

namespace App\Game\Domain\Sport;

/**
 * No sport in particular. The fallback for everything that is not in the list
 * and for anyone who just wants a scoreboard: points by hand, entries in plain
 * words. Every game that existed before sports did is one of these.
 */
final class OpenSport implements Sport
{
    public const KEY = 'offen';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Offen';
    }

    public function icon(): string
    {
        return '🏅';
    }

    public function events(): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace App\Game\Domain\Sport;

final class Handball implements Sport
{
    public function key(): string
    {
        return 'handball';
    }

    public function label(): string
    {
        return 'Handball';
    }

    public function icon(): string
    {
        return '🤾';
    }

    public function events(): array
    {
        return [
            new SportEvent('tor', 'Tor', '🤾', 1),
            new SportEvent('siebenmeter', 'Siebenmeter', '🥅', 1),
            new SportEvent('siebenmeter-gehalten', 'Siebenmeter gehalten', '🧤'),
            new SportEvent('zeitstrafe', 'Zeitstrafe', '⏳'),
            new SportEvent('rote-karte', 'Rote Karte', '🟥'),
        ];
    }
}

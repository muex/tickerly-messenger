<?php

declare(strict_types=1);

namespace App\Game\Domain\Sport;

final class AmericanFootball implements Sport
{
    public function key(): string
    {
        return 'american-football';
    }

    public function label(): string
    {
        return 'American Football';
    }

    public function icon(): string
    {
        return '🏈';
    }

    public function events(): array
    {
        return [
            new SportEvent('touchdown', 'Touchdown', '🏈', 6),
            new SportEvent('extrapunkt', 'Extrapunkt', '🦶', 1),
            new SportEvent('two-point-conversion', '2-Punkt-Conversion', '✌️', 2),
            new SportEvent('field-goal', 'Field Goal', '🥅', 3),
            new SportEvent('safety', 'Safety', '🛡', 2),
            new SportEvent('interception', 'Interception', '🙌'),
            new SportEvent('fumble', 'Fumble', '💨'),
            new SportEvent('strafe', 'Strafe', '🚩'),
        ];
    }
}

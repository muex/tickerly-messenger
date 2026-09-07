<?php

declare(strict_types=1);

namespace App\Game\Domain\Sport;

final class Basketball implements Sport
{
    public function key(): string
    {
        return 'basketball';
    }

    public function label(): string
    {
        return 'Basketball';
    }

    public function icon(): string
    {
        return '🏀';
    }

    public function events(): array
    {
        return [
            // Keycaps rather than pictures: what matters in a basketball ticker
            // is how many points the entry was worth.
            new SportEvent('dreier', '3er', '3️⃣', 3),
            new SportEvent('zweier', '2er', '2️⃣', 2),
            new SportEvent('freiwurf', 'Freiwurf', '1️⃣', 1),
            new SportEvent('foul', 'Foul', '✋'),
            new SportEvent('auszeit', 'Auszeit', '⏱'),
        ];
    }
}

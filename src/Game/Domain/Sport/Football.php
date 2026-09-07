<?php

declare(strict_types=1);

namespace App\Game\Domain\Sport;

final class Football implements Sport
{
    public function key(): string
    {
        return 'fussball';
    }

    public function label(): string
    {
        return 'Fußball';
    }

    public function icon(): string
    {
        return '⚽';
    }

    public function events(): array
    {
        return [
            new SportEvent('tor', 'Tor', '⚽', 1),
            new SportEvent('elfmeter-tor', 'Elfmeter', '🥅', 1),
            new SportEvent('eigentor', 'Eigentor', '🙈', 1),
            new SportEvent('elfmeter-verschossen', 'Elfmeter verschossen', '❌'),
            new SportEvent('gelbe-karte', 'Gelbe Karte', '🟨'),
            new SportEvent('gelb-rote-karte', 'Gelb-Rot', '🟨🟥'),
            new SportEvent('rote-karte', 'Rote Karte', '🟥'),
            new SportEvent('wechsel', 'Wechsel', '🔄'),
        ];
    }
}

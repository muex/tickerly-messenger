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
            // No "Eigentor": the entry is recorded for the team the point goes
            // to, so an own goal is a Tor for the other side with a note saying
            // who put it in. Listed here it would credit the wrong team.
            new SportEvent('tor', 'Tor', '⚽', 1),
            new SportEvent('elfmeter-tor', 'Elfmeter', '🥅', 1),
            new SportEvent('elfmeter-verschossen', 'Elfmeter verschossen', '❌'),
            new SportEvent('gelbe-karte', 'Gelbe Karte', '🟨'),
            new SportEvent('gelb-rote-karte', 'Gelb-Rot', '🟨🟥'),
            new SportEvent('rote-karte', 'Rote Karte', '🟥'),
            new SportEvent('wechsel', 'Wechsel', '🔄'),
        ];
    }
}

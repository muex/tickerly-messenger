<?php

namespace App\Game\Application\Command;

use App\Game\Domain\Side;
use App\Shared\Domain\Command;
use Symfony\Component\Uid\Uuid;

/**
 * One tap on a sport's button: it writes the ticker entry and moves the score
 * in a single step, so a three-pointer is not two separate acts.
 */
final class RecordSportEvent implements Command
{
    public function __construct(
        private Uuid $gameId,
        private string $eventKey,
        private Side $side,
        private ?string $timecode = null,
        private ?string $note = null,
    ) {}

    public function getGameId(): Uuid
    {
        return $this->gameId;
    }

    public function getEventKey(): string
    {
        return $this->eventKey;
    }

    public function getSide(): Side
    {
        return $this->side;
    }

    public function getTimecode(): ?string
    {
        return $this->timecode;
    }

    /** What the owner typed alongside it, if anything. */
    public function getNote(): ?string
    {
        return $this->note;
    }
}

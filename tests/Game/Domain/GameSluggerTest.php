<?php

namespace App\Tests\Game\Domain;

use App\Entity\Game;
use App\Game\Domain\GameSlugger;
use App\Repository\GameRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

class GameSluggerTest extends TestCase
{
    public function testItBuildsASlugFromTeamsAndKickoffDate(): void
    {
        $slugger = new GameSlugger(new AsciiSlugger(), $this->repositoryFindingNothing());

        $this->assertSame(
            'falcons-vs-sharks-2026-08-25',
            $slugger->slugFor('Falcons', 'Sharks', new \DateTime('2026-08-25 19:30')),
        );
    }

    public function testItTransliteratesGermanUmlauts(): void
    {
        $slugger = new GameSlugger(new AsciiSlugger(), $this->repositoryFindingNothing());

        $this->assertSame(
            'woelfe-vs-strassen-sv-2026-09-02',
            $slugger->slugFor('Wölfe', 'Straßen SV', new \DateTime('2026-09-02 18:00')),
        );
    }

    public function testItAppendsACounterWhenTheFixtureRepeatsOnTheSameDay(): void
    {
        $repository = $this->createStub(GameRepository::class);
        // The plain slug and the "-2" variant are taken, the "-3" is free.
        $repository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria): ?Game => \in_array(
                $criteria['slug'],
                ['falcons-vs-sharks-2026-08-25', 'falcons-vs-sharks-2026-08-25-2'],
                true,
            ) ? new Game() : null,
        );

        $slugger = new GameSlugger(new AsciiSlugger(), $repository);

        $this->assertSame(
            'falcons-vs-sharks-2026-08-25-3',
            $slugger->slugFor('Falcons', 'Sharks', new \DateTime('2026-08-25 21:00')),
        );
    }

    private function repositoryFindingNothing(): GameRepository
    {
        $repository = $this->createStub(GameRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        return $repository;
    }
}

<?php

namespace App\Tests\Game\Domain\Sport;

use App\Game\Domain\Sport\OpenSport;
use App\Game\Domain\Sport\Sport;
use App\Game\Domain\Sport\SportCatalog;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The catalog as the container assembles it — the point being that adding a
 * sport is one class and nothing else.
 */
class SportCatalogTest extends KernelTestCase
{
    public function testEverySportClassIsInTheCatalog(): void
    {
        $keys = array_keys($this->catalog()->all());

        $this->assertContains('fussball', $keys);
        $this->assertContains('basketball', $keys);
        $this->assertContains('american-football', $keys);
    }

    public function testTheOpenScoreboardComesFirst(): void
    {
        // It is the default and the fallback, so it leads the select rather
        // than sitting wherever the alphabet or the container puts it.
        $this->assertSame(OpenSport::KEY, array_key_first($this->catalog()->all()));
    }

    public function testAnUnknownSportFallsBackToTheOpenScoreboard(): void
    {
        // A game whose sport was retired still has to render.
        $this->assertSame(OpenSport::KEY, $this->catalog()->get('rasenschach')->key());
        $this->assertSame(OpenSport::KEY, $this->catalog()->get(null)->key());
    }

    public function testEventsAreOnlyFoundInTheirOwnSport(): void
    {
        $catalog = $this->catalog();

        $this->assertSame(3, $catalog->eventFor('basketball', 'dreier')?->points);
        $this->assertNull($catalog->eventFor('fussball', 'dreier'));
        $this->assertNull($catalog->eventFor('offen', 'tor'));
    }

    public function testNoSportShipsADuplicateEventKey(): void
    {
        foreach ($this->catalog()->all() as $sport) {
            $keys = array_map(static fn ($event): string => $event->key, $sport->events());

            $this->assertSame(
                array_unique($keys),
                $keys,
                sprintf('%s has two events under the same key.', $sport->key()),
            );
        }
    }

    public function testOnlyScoringEventsCarryPoints(): void
    {
        foreach ($this->catalog()->all() as $sport) {
            foreach ($sport->events() as $event) {
                // A button may add points or none; taking them away is what the
                // minus control is for.
                $this->assertGreaterThanOrEqual(0, $event->points, $sport->key() . '/' . $event->key);
            }
        }
    }

    private function catalog(): SportCatalog
    {
        self::bootKernel();

        return self::getContainer()->get(SportCatalog::class);
    }
}

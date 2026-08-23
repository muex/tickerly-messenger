<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class UserSessionTest extends TestCase
{
    public function testAUserSurvivesAnUnserializeRoundTrip(): void
    {
        $user = (new User())->setEmail('user@example.com')->setPassword('hash');

        $restored = unserialize(serialize($user));

        $this->assertTrue($user->getId()->equals($restored->getId()));
        $this->assertSame('user@example.com', $restored->getEmail());
    }

    public function testASessionFromBeforeTheUuidSwitchDoesNotFatal(): void
    {
        $prefix = sprintf("\0%s\0", User::class);
        $user = (new \ReflectionClass(User::class))->newInstanceWithoutConstructor();

        // Shape of a session written while the id was still an auto-increment int.
        $user->__unserialize([
            $prefix . 'id' => 42,
            $prefix . 'email' => 'legacy@example.com',
            $prefix . 'roles' => [],
            $prefix . 'password' => 'hash',
        ]);

        // A well-typed id that matches no row: the provider cannot refresh this
        // user, so the stale session is logged out rather than blowing up.
        $this->assertInstanceOf(Uuid::class, $user->getId());
        $this->assertSame('legacy@example.com', $user->getEmail());
    }
}

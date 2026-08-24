<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserSessionTest extends TestCase
{
    public function testAUserSurvivesAnUnserializeRoundTrip(): void
    {
        $user = (new User())->setEmail('user@example.com')->setPassword('hash');

        $restored = unserialize(serialize($user));

        $this->assertTrue($user->getId()->equals($restored->getId()));
        $this->assertSame('user@example.com', $restored->getEmail());
    }
}

<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserCheckerTest extends TestCase
{
    public function testActiveUserPassesTheCheck(): void
    {
        $checker = new UserChecker();
        $checker->checkPreAuth((new User())->setEmail('active@example.com'));

        $this->expectNotToPerformAssertions();
    }

    public function testDeactivatedUserIsRejected(): void
    {
        $user = (new User())->setEmail('blocked@example.com')->setActive(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);

        (new UserChecker())->checkPreAuth($user);
    }
}

<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Refuses authentication for users an admin has deactivated. Checked before
 * the credentials, so a deactivated account cannot log in even with the
 * correct password, and before every request of an existing session, so a
 * user that is deactivated while logged in is thrown out on the next request.
 */
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof User && !$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Dieses Konto wurde deaktiviert.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}

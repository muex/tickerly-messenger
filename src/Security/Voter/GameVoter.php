<?php

namespace App\Security\Voter;

use App\Entity\Game;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants access to a Game only to its owner. Used to guard every
 * mutating action on a specific game (edit, delete, scoring, events).
 */
class GameVoter extends Voter
{
    public const EDIT = 'GAME_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::EDIT && $subject instanceof Game;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Game $subject */
        return $subject->getOwner() === $user;
    }
}

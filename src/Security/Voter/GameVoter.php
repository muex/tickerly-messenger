<?php

namespace App\Security\Voter;

use App\Entity\Game;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Grants access to a Game only to its owner.
 *
 * Two attributes, because ownership and "the game is still on" are different
 * questions. GAME_EDIT guards what stays possible after the final whistle —
 * fixing a misspelled team name, deleting the game. GAME_SCORE guards what the
 * whistle ends: points and ticker entries.
 */
class GameVoter extends Voter
{
    public const EDIT = 'GAME_EDIT';
    public const SCORE = 'GAME_SCORE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::EDIT, self::SCORE], true) && $subject instanceof Game;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Game $subject */
        if ($subject->getOwner() !== $user) {
            return false;
        }

        return $attribute !== self::SCORE || !$subject->isFinished();
    }
}

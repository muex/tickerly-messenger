<?php

namespace App\Tests\Security\Voter;

use App\Entity\Game;
use App\Entity\User;
use App\Security\Voter\GameVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class GameVoterTest extends TestCase
{
    private GameVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new GameVoter();
    }

    public function testOwnerIsGrantedEdit(): void
    {
        $owner = new User();
        $game = (new Game())->setOwner($owner);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->tokenFor($owner), $game, [GameVoter::EDIT])
        );
    }

    public function testNonOwnerIsDeniedEdit(): void
    {
        $game = (new Game())->setOwner(new User());
        $someoneElse = new User();

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->tokenFor($someoneElse), $game, [GameVoter::EDIT])
        );
    }

    public function testAnonymousIsDeniedEdit(): void
    {
        $game = (new Game())->setOwner(new User());

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $game, [GameVoter::EDIT])
        );
    }

    public function testOwnerIsGrantedScoreWhileTheGameIsOn(): void
    {
        $owner = new User();
        $game = (new Game())->setOwner($owner);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->tokenFor($owner), $game, [GameVoter::SCORE])
        );
    }

    public function testTheFinalWhistleTakesScoringAwayFromTheOwner(): void
    {
        $owner = new User();
        $game = (new Game())->setOwner($owner)->setFinishedAt(new \DateTimeImmutable());

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->tokenFor($owner), $game, [GameVoter::SCORE])
        );
    }

    public function testAFinishedGameStaysEditable(): void
    {
        $owner = new User();
        $game = (new Game())->setOwner($owner)->setFinishedAt(new \DateTimeImmutable());

        // Otherwise a misspelled team name would be frozen with the score, and
        // reopening the game would be impossible.
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->tokenFor($owner), $game, [GameVoter::EDIT])
        );
    }

    public function testAbstainsOnUnsupportedAttribute(): void
    {
        $owner = new User();
        $game = (new Game())->setOwner($owner);

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->tokenFor($owner), $game, ['SOME_OTHER_ATTRIBUTE'])
        );
    }

    public function testAbstainsOnNonGameSubject(): void
    {
        $owner = new User();

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->tokenFor($owner), new \stdClass(), [GameVoter::EDIT])
        );
    }

    private function tokenFor(User $user): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}

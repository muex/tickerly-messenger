<?php

namespace App\Tests\Support;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base for the tests that need real rows: authorization, what the game page
 * renders, and the share metadata all depend on a Game coming out of the
 * database.
 *
 * The suite still runs without a database — these tests skip themselves when
 * none is reachable, so `php bin/phpunit` stays green on a machine with no
 * container running. Prepare one with:
 *
 *     docker compose up -d database
 *     APP_ENV=test php bin/console doctrine:migrations:migrate
 *
 * Every test runs inside a transaction that is rolled back afterwards, so the
 * rows never outlive the test that made them.
 */
abstract class FunctionalTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Without this the kernel is rebooted between requests, which would take
        // a fresh connection and leave the transaction below behind.
        $this->client->disableReboot();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->skipUnlessDatabaseIsReady();
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();

        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        parent::tearDown();
    }

    protected function createUser(string $email, array $roles = []): User
    {
        $user = (new User())->setEmail($email)->setRoles($roles)->setPassword('not-used-in-these-tests');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function createGame(User $owner, string $slug, bool $active = true, int $homepoints = 0, int $awaypoints = 0): Game
    {
        $game = (new Game())
            ->setHome('Falcons')
            ->setAway('Sharks')
            ->setLocation('Stadthalle')
            ->setDatetime(new \DateTime('2026-12-01 19:30'))
            ->setSlug($slug)
            ->setActive($active)
            ->setHomepoints($homepoints)
            ->setAwaypoints($awaypoints)
            ->setOwner($owner);

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        return $game;
    }

    private function skipUnlessDatabaseIsReady(): void
    {
        try {
            $this->entityManager->getConnection()->executeQuery('SELECT 1 FROM game LIMIT 1');
        } catch (DbalException | \Doctrine\DBAL\Driver\Exception $e) {
            $this->markTestSkipped('No migrated test database reachable: ' . $e->getMessage());
        }
    }
}

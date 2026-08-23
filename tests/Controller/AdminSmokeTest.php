<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminSmokeTest extends WebTestCase
{
    public function testAdminAreaRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        $this->assertResponseRedirects('/login');
    }

    #[DataProvider('toggleRoutes')]
    public function testTogglesRejectGet(string $path): void
    {
        $client = static::createClient();
        // Activation must not be triggerable by a link, prefetch or crawler.
        $client->request('GET', $path);

        $this->assertResponseStatusCodeSame(405);
    }

    public static function toggleRoutes(): iterable
    {
        yield 'user' => ['/admin/users/01996f3a-4c21-7b2e-9f10-2c8d5e7a1b44/toggle'];
        yield 'game' => ['/admin/games/01996f3a-4c21-7b2e-9f10-2c8d5e7a1b44/toggle'];
    }
}

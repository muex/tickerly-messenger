<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecuritySmokeTest extends WebTestCase
{
    public function testLandingPageIsPublic(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateGameRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/games/new');

        $this->assertResponseRedirects('/login');
    }

    public function testScoreRoutesRejectGet(): void
    {
        $client = static::createClient();
        // Scoring must not be triggerable by a GET (link, prefetch, crawler).
        $client->request('GET', '/games/falcons-vs-sharks-2026-08-25/increasehome');

        $this->assertResponseStatusCodeSame(405);
    }
}

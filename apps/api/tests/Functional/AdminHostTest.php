<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Le back-office ne répond que sur son domaine (ADMIN_HOST, « localhost » en test). */
final class AdminHostTest extends WebTestCase
{
    public function testBackOfficeIsHiddenOnOtherHosts(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/login', server: ['HTTP_HOST' => 'api.genius-at-work.com']);

        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminHostLeadsToTheBackOffice(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseRedirects('/admin');
    }

    public function testApiStaysOnItsHost(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v1/health', server: ['HTTP_HOST' => 'api.genius-at-work.com']);

        self::assertResponseIsSuccessful();
    }
}

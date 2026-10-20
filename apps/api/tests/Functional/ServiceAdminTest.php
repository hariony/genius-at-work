<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Content\Service;
use App\Domain\Content\ServiceRepository;
use App\Domain\Content\ServiceSlug;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ServiceAdminTest extends WebTestCase
{
    use ContentTestTools;

    public function testTheSixServicesAreListed(): void
    {
        $this->import();
        $this->login();
        $this->client->request('GET', '/admin/content/services');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(7, '.service-item');
    }

    public function testServiceTextsAreSaved(): void
    {
        $this->import();
        $this->login();
        $this->client->request('GET', '/admin/content/services/websites');
        $this->client->submitForm('Enregistrer', [
            'service[title_fr]' => 'Sites web',
            'service[scope_fr]' => "Design\n\n  Développement  ",
            'service[technologies]' => 'Astro, WordPress',
        ]);

        self::assertResponseRedirects('/admin/content/services/websites');
        $details = $this->service()->details();
        self::assertSame('Sites web', $details->title->fr);
        self::assertSame(['Design', 'Développement'], $details->scopeFr);
        self::assertSame(['Astro', 'WordPress'], $details->technologies);
    }

    public function testAnEmptyTranslationIsRefused(): void
    {
        $this->import();
        $this->login();
        $this->client->request('GET', '/admin/content/services/websites');
        $this->client->submitForm('Enregistrer', ['service[intro_en]' => '']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-intro_en', 'anglais');
        self::assertNotSame('', $this->service()->details()->intro->en);
    }

    public function testUnknownServiceIsNotFound(): void
    {
        $this->login();
        $this->client->request('GET', '/admin/content/services/blog');

        self::assertResponseStatusCodeSame(404);
    }

    private function service(): Service
    {
        $this->em()->clear();
        /** @var ServiceRepository $services */
        $services = static::getContainer()->get(ServiceRepository::class);
        $service = $services->get(ServiceSlug::Websites);
        self::assertNotNull($service);

        return $service;
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Lead\BudgetRange;
use App\Domain\Lead\ContactDetails;
use App\Domain\Lead\Lead;
use App\Domain\Lead\LeadId;
use App\Domain\Lead\LeadStatus;
use App\Domain\Lead\Locale;
use App\Domain\Lead\ProjectBrief;
use App\Domain\Lead\ProjectType;
use App\Infrastructure\Security\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use OTPHP\TOTP;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminLeadTest extends WebTestCase
{
    private const string PASSWORD = 'test-password-not-secret';
    private const string TOTP_SECRET = 'JBSWY3DPEHPK3PXP';
    private const string LEAD_ID = '01JABCDEFGHJKMNPQRSTVWXYZ0';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        /** @var CacheItemPoolInterface $limits */
        $limits = static::getContainer()->get('cache.rate_limiter');
        $limits->clear();
        $em = $this->em();
        $schema = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $this->addLead(self::LEAD_ID, 'GAW-2026-00001', 'Jeanne Martin', 'ACME');
        $this->addLead('01JABCDEFGHJKMNPQRSTVWXYZ1', 'GAW-2026-00002', 'Paul Rakoto', null);
    }

    public function testBackOfficeRequiresALogin(): void
    {
        $this->client->request('GET', '/admin/leads');

        self::assertResponseRedirects('/admin/login');
    }

    public function testAdminCanLogInAndSeeTheLeads(): void
    {
        $this->createAdmin();
        $this->client->request('GET', '/admin/login');
        $this->client->submitForm('Se connecter', ['_username' => 'admin@example.com', '_password' => self::PASSWORD]);

        self::assertResponseRedirects('/admin/leads');
        $this->client->followRedirect();
        self::assertResponseRedirects('/admin/2fa');
        $this->client->followRedirect();
        $this->client->submitForm('Valider', ['_auth_code' => TOTP::createFromSecret(self::TOTP_SECRET)->now()]);

        self::assertResponseRedirects('/admin/leads');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Demandes de contact');
        self::assertSelectorCount(2, 'tbody tr');
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex, nofollow');
        self::assertStringContainsString('no-store', (string) $this->client->getResponse()->headers->get('Cache-Control'));
    }

    public function testWrongPasswordIsRejected(): void
    {
        $this->createAdmin();
        $this->client->request('GET', '/admin/login');
        $this->client->submitForm('Se connecter', ['_username' => 'admin@example.com', '_password' => 'wrong-password']);
        $this->client->followRedirect();

        self::assertSelectorTextContains('.flash--error', 'Adresse e-mail ou mot de passe incorrect.');
    }

    public function testListCanBeSearchedAndFilteredByStatus(): void
    {
        $this->client->loginUser($this->createAdmin(), 'admin');

        $this->client->request('GET', '/admin/leads', ['q' => 'rakoto']);
        self::assertSelectorCount(1, 'tbody tr');
        self::assertSelectorTextContains('tbody', 'GAW-2026-00002');

        $this->client->request('GET', '/admin/leads', ['status' => 'won']);
        self::assertSelectorTextContains('.empty-title', 'Aucune demande ne correspond.');
    }

    public function testStatusChangeIsSavedWithAValidToken(): void
    {
        $this->client->loginUser($this->createAdmin(), 'admin');
        $this->client->request('GET', '/admin/leads/'.self::LEAD_ID);
        self::assertSelectorTextContains('.message', 'Nous voulons un outil de suivi des commandes.');

        $this->client->submitForm('Enregistrer le statut', ['status' => 'qualified']);

        self::assertResponseRedirects('/admin/leads/'.self::LEAD_ID);
        self::assertSame(LeadStatus::Qualified, $this->lead()->status());
    }

    public function testStatusChangeWithoutTokenIsRefused(): void
    {
        $this->client->loginUser($this->createAdmin(), 'admin');
        $this->client->request('POST', '/admin/leads/'.self::LEAD_ID.'/status', ['status' => 'won', '_token' => 'forged']);

        self::assertSame(LeadStatus::New, $this->lead()->status());
    }

    public function testAnonymizationNeedsTheReferenceAsConfirmation(): void
    {
        $this->client->loginUser($this->createAdmin(), 'admin');
        $this->client->request('GET', '/admin/leads/'.self::LEAD_ID.'/anonymize');
        $this->client->submitForm('Effacer les données personnelles', ['confirm_reference' => 'GAW-2026-00002']);
        self::assertFalse($this->lead()->isAnonymized());

        $this->client->submitForm('Effacer les données personnelles', ['confirm_reference' => 'GAW-2026-00001']);
        self::assertResponseRedirects('/admin/leads/'.self::LEAD_ID);
        $lead = $this->lead();
        self::assertTrue($lead->isAnonymized());
        self::assertSame(Lead::ANONYMIZED, $lead->email());
        self::assertSame(ProjectType::WebApp, $lead->projectType(), 'Conservé pour les statistiques.');
    }

    public function testPublicApiStaysStateless(): void
    {
        $this->client->request('GET', '/api/v1/health');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->client->getResponse()->headers->getCookies());
    }

    private function createAdmin(): AdminUser
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $admin = new AdminUser('01JABCDEFGHJKMNPQRSTVWXYZA', 'admin@example.com', new \DateTimeImmutable());
        $admin->setPassword($hasher->hashPassword($admin, self::PASSWORD));
        $admin->enableTotp(self::TOTP_SECRET);
        $this->em()->persist($admin);
        $this->em()->flush();

        return $admin;
    }

    private function addLead(string $id, string $reference, string $name, ?string $company): void
    {
        $lead = Lead::submit(
            id: LeadId::fromString($id),
            reference: $reference,
            contact: new ContactDetails($name, strtolower(str_replace(' ', '.', $name)).'@example.com', null, $company),
            brief: new ProjectBrief(ProjectType::WebApp, 'Nous voulons un outil de suivi des commandes.', BudgetRange::From10kTo30k),
            locale: Locale::French,
            sourceUrl: '/contact/',
            now: new \DateTimeImmutable('-2 days'),
        );
        $this->em()->persist($lead);
        $this->em()->flush();
    }

    private function lead(): Lead
    {
        $this->em()->clear();
        $lead = $this->em()->find(Lead::class, self::LEAD_ID);
        self::assertNotNull($lead);

        return $lead;
    }

    private function em(): EntityManagerInterface
    {
        /* @var EntityManagerInterface */
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}

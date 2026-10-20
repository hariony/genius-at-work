<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Lead\Event\LeadSubmitted;
use App\Domain\Lead\Lead;
use App\Tests\Double\StubBotVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class ContactRequestTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /** @var CacheItemPoolInterface $limits */
        $limits = static::getContainer()->get('cache.rate_limiter_test');
        $limits->clear();
        $em = $this->em();
        $schema = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);
    }

    public function testValidRequestCreatesALeadAndQueuesNotifications(): void
    {
        $this->post($this->payload());

        self::assertResponseStatusCodeSame(201);
        self::assertMatchesRegularExpression('/^GAW-\d{4}-00001$/', $this->reference());

        $leads = $this->em()->getRepository(Lead::class)->findAll();
        self::assertCount(1, $leads);
        self::assertSame('jeanne@example.com', $leads[0]->email());
        self::assertNull($leads[0]->phone(), 'Un champ facultatif vide n’est pas stocké.');

        /** @var InMemoryTransport $async */
        $async = static::getContainer()->get('messenger.transport.async');
        $sent = $async->getSent();
        self::assertCount(1, $sent);
        self::assertInstanceOf(LeadSubmitted::class, $sent[0]->getMessage());
    }

    public function testReferencesAreSequential(): void
    {
        $this->client->disableReboot();
        $this->post($this->payload());
        $this->post($this->payload());

        self::assertStringEndsWith('-00002', $this->reference());
    }

    public function testInvalidRequestReturnsStableErrorCodes(): void
    {
        $this->post($this->payload([
            'projectType' => 'spaceship',
            'message' => 'court',
            'email' => 'not-an-email',
            'phone' => 'abc',
            'consent' => false,
        ]));

        self::assertResponseStatusCodeSame(422);
        $violations = $this->violations();
        self::assertSame('invalid_choice', $violations['projectType']);
        self::assertSame('too_short', $violations['message']);
        self::assertSame('invalid_email', $violations['email']);
        self::assertSame('invalid_phone', $violations['phone']);
        self::assertSame('consent_required', $violations['consent']);
        self::assertSame([], $this->em()->getRepository(Lead::class)->findAll());
    }

    public function testHoneypotIsAcceptedButNotStored(): void
    {
        $this->post($this->payload(['website' => 'https://spam.example']));

        self::assertResponseStatusCodeSame(201);
        self::assertSame([], $this->em()->getRepository(Lead::class)->findAll());
    }

    public function testTooFastSubmissionIsNotStored(): void
    {
        $this->post($this->payload(['startedAt' => (int) (microtime(true) * 1000)]));

        self::assertResponseStatusCodeSame(201);
        self::assertSame([], $this->em()->getRepository(Lead::class)->findAll());
    }

    public function testFailedBotCheckIsReportedOnTheTokenField(): void
    {
        $this->post($this->payload(['turnstileToken' => StubBotVerifier::REJECTED_TOKEN]));

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['turnstileToken' => 'bot_check_failed'], $this->violations());
    }

    public function testSubmissionsAreRateLimitedPerClient(): void
    {
        $this->client->disableReboot();
        for ($i = 0; $i < 5; ++$i) {
            $this->post($this->payload());
            self::assertResponseStatusCodeSame(201);
        }

        $this->post($this->payload());
        self::assertResponseStatusCodeSame(429);
    }

    public function testCorsAllowsTheSiteOrigin(): void
    {
        $this->client->request('OPTIONS', '/api/v1/contact-requests', server: [
            'HTTP_ORIGIN' => 'http://127.0.0.1:4321',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        self::assertResponseHeaderSame('access-control-allow-origin', 'http://127.0.0.1:4321');
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'projectType' => 'website',
            'message' => 'Nous souhaitons refondre notre site institutionnel bilingue.',
            'fullName' => 'Jeanne Martin',
            'email' => 'jeanne@example.com',
            'phone' => '',
            'consent' => true,
            'locale' => 'fr',
            'sourceUrl' => '/contact/',
            'website' => '',
            'startedAt' => (int) (microtime(true) * 1000) - 60_000,
            'turnstileToken' => 'test-token',
        ], $overrides);
    }

    /** @param array<string, mixed> $payload */
    private function post(array $payload): void
    {
        $this->client->request('POST', '/api/v1/contact-requests', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'REMOTE_ADDR' => '203.0.113.10',
        ], content: json_encode($payload, \JSON_THROW_ON_ERROR));
    }

    private function reference(): string
    {
        /** @var array{reference: string} $body */
        $body = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        return $body['reference'];
    }

    /** @return array<string, string> propertyPath → code */
    private function violations(): array
    {
        /** @var array{violations: list<array{propertyPath: string, message: string}>} $body */
        $body = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        return array_column($body['violations'], 'message', 'propertyPath');
    }

    private function em(): EntityManagerInterface
    {
        /* @var EntityManagerInterface */
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}

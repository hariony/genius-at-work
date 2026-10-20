<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\Contact\Port\BotVerifier;
use App\Application\Contact\Port\EventPublisher;
use App\Application\Contact\Port\IdGenerator;
use App\Application\Contact\Port\SpamGuard;
use App\Application\Contact\Port\SubmissionThrottle;
use App\Application\Contact\SubmitContactRequest\SubmissionRejected;
use App\Application\Contact\SubmitContactRequest\SubmitContactRequestCommand;
use App\Application\Contact\SubmitContactRequest\SubmitContactRequestHandler;
use App\Domain\Lead\Event\LeadSubmitted;
use App\Domain\Lead\Lead;
use App\Domain\Lead\LeadId;
use App\Domain\Lead\LeadRepository;
use App\Domain\Lead\Locale;
use App\Domain\Lead\ProjectType;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\MockClock;

final class SubmitContactRequestHandlerTest extends TestCase
{
    private InMemoryLeads $leads;
    /** @var list<object> */
    private array $published = [];

    protected function setUp(): void
    {
        $this->leads = new InMemoryLeads();
        $this->published = [];
    }

    public function testStoresTheLeadAndPublishesTheEvent(): void
    {
        $result = $this->handler()($this->command());

        self::assertTrue($result->stored);
        self::assertSame('GAW-2026-00001', $result->reference);
        self::assertCount(1, $this->leads->items);
        self::assertInstanceOf(LeadSubmitted::class, $this->published[0] ?? null);
    }

    public function testAQueueFailureDoesNotLoseTheLead(): void
    {
        $result = $this->handler(publisherFails: true)($this->command());

        self::assertTrue($result->stored);
        self::assertCount(1, $this->leads->items);
    }

    public function testSpamIsSilentlyDiscarded(): void
    {
        $result = $this->handler(spam: true)($this->command());

        self::assertFalse($result->stored);
        self::assertSame([], $this->leads->items);
        self::assertSame([], $this->published);
    }

    public function testThrottledClientsAreRejectedBeforeAnythingElse(): void
    {
        $this->expectExceptionObject(SubmissionRejected::rateLimited());
        $this->handler(throttled: true)($this->command());
    }

    public function testFailedBotCheckIsRejected(): void
    {
        try {
            $this->handler(bot: false)($this->command());
            self::fail('La demande aurait dû être refusée.');
        } catch (SubmissionRejected $rejection) {
            self::assertSame(SubmissionRejected::BOT_CHECK_FAILED, $rejection->reason);
            self::assertSame([], $this->leads->items);
        }
    }

    private function handler(bool $spam = false, bool $throttled = false, bool $bot = true, bool $publisherFails = false): SubmitContactRequestHandler
    {
        $published = &$this->published;

        return new SubmitContactRequestHandler(
            throttle: new class(!$throttled) implements SubmissionThrottle {
                public function __construct(private bool $accept)
                {
                }

                public function consume(string $clientKey): bool
                {
                    return $this->accept;
                }
            },
            spamGuard: new class($spam) implements SpamGuard {
                public function __construct(private bool $spam)
                {
                }

                public function isSpam(SubmitContactRequestCommand $command): bool
                {
                    return $this->spam;
                }
            },
            botVerifier: new class($bot) implements BotVerifier {
                public function __construct(private bool $valid)
                {
                }

                public function verify(string $token, ?string $clientIp): bool
                {
                    return $this->valid;
                }
            },
            leads: $this->leads,
            ids: new class implements IdGenerator {
                public function nextLeadId(): LeadId
                {
                    return LeadId::fromString('01JABCDEFGHJKMNPQRSTVWXYZ0');
                }
            },
            events: new class($published, $publisherFails) implements EventPublisher {
                /** @param list<object> $published */
                public function __construct(private array &$published, private bool $fails)
                {
                }

                public function publish(object ...$events): void
                {
                    if ($this->fails) {
                        throw new \RuntimeException('File indisponible.');
                    }
                    array_push($this->published, ...$events);
                }
            },
            clock: new MockClock('2026-10-19 10:00:00'),
            logger: new NullLogger(),
        );
    }

    private function command(): SubmitContactRequestCommand
    {
        return new SubmitContactRequestCommand(
            projectType: ProjectType::Website,
            message: 'Nous souhaitons refondre notre site institutionnel.',
            budgetRange: null,
            timeline: null,
            fullName: 'Jeanne Martin',
            email: 'jeanne@example.com',
            phone: null,
            company: null,
            locale: Locale::French,
            sourceUrl: '/contact/',
            honeypot: '',
            startedAt: 1,
            turnstileToken: 'token',
            clientIp: '203.0.113.10',
        );
    }
}

final class InMemoryLeads implements LeadRepository
{
    /** @var array<string, Lead> */
    public array $items = [];

    public function save(Lead $lead): void
    {
        $this->items[$lead->id()->value] = $lead;
    }

    public function get(LeadId $id): ?Lead
    {
        return $this->items[$id->value] ?? null;
    }

    public function nextReference(int $year): string
    {
        return \sprintf('GAW-%d-%05d', $year, \count($this->items) + 1);
    }

    public function findNotAnonymizedLastContactedBefore(\DateTimeImmutable $limit): iterable
    {
        return array_filter($this->items, static fn (Lead $lead) => !$lead->isAnonymized() && $lead->lastContactAt() < $limit);
    }
}

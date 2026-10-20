<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Lead\ContactDetails;
use App\Domain\Lead\Event\LeadSubmitted;
use App\Domain\Lead\Lead;
use App\Domain\Lead\LeadId;
use App\Domain\Lead\LeadStatus;
use App\Domain\Lead\Locale;
use App\Domain\Lead\ProjectBrief;
use App\Domain\Lead\ProjectType;
use PHPUnit\Framework\TestCase;

final class LeadTest extends TestCase
{
    public static function newLead(?\DateTimeImmutable $now = null): Lead
    {
        return Lead::submit(
            id: LeadId::fromString('01JABCDEFGHJKMNPQRSTVWXYZ0'),
            reference: 'GAW-2026-00001',
            contact: new ContactDetails('Jeanne Martin', 'Jeanne.Martin@Example.com', '+33 6 00 00 00 00', 'ACME'),
            brief: new ProjectBrief(ProjectType::WebApp, 'Nous voulons un outil de suivi des commandes.'),
            locale: Locale::French,
            sourceUrl: '/contact/',
            now: $now ?? new \DateTimeImmutable('2026-10-19 10:00:00'),
        );
    }

    public function testSubmitCreatesANewLeadAndRecordsAnEvent(): void
    {
        $lead = self::newLead();

        self::assertSame(LeadStatus::New, $lead->status());
        self::assertSame('jeanne.martin@example.com', $lead->email());
        self::assertEquals($lead->createdAt(), $lead->consentAt());

        $events = $lead->pullEvents();
        self::assertEquals([new LeadSubmitted('01JABCDEFGHJKMNPQRSTVWXYZ0')], $events);
        self::assertSame([], $lead->pullEvents(), 'Les événements ne sont publiés qu’une fois.');
    }

    public function testStatusChangeDoesNotCountAsAContact(): void
    {
        $lead = self::newLead();
        $lead->changeStatus(LeadStatus::Qualified);

        self::assertSame(LeadStatus::Qualified, $lead->status());
        self::assertEquals($lead->createdAt(), $lead->lastContactAt());
    }

    public function testAnonymizeErasesPersonalDataOnly(): void
    {
        $lead = self::newLead();
        $lead->anonymize(new \DateTimeImmutable('2029-10-20'));

        self::assertTrue($lead->isAnonymized());
        self::assertSame(Lead::ANONYMIZED, $lead->fullName());
        self::assertSame(Lead::ANONYMIZED, $lead->email());
        self::assertSame(Lead::ANONYMIZED, $lead->message());
        self::assertNull($lead->phone());
        self::assertNull($lead->company());
        self::assertNull($lead->sourceUrl());
        // Conservé pour les statistiques : type de projet, langue, dates, référence.
        self::assertSame(ProjectType::WebApp, $lead->projectType());
        self::assertSame('GAW-2026-00001', $lead->reference());
    }

    public function testRejectsAnInvalidId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LeadId::fromString('not-an-ulid');
    }
}

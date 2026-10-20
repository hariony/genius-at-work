<?php

declare(strict_types=1);

namespace App\Domain\Lead;

use App\Domain\Lead\Event\LeadSubmitted;

/**
 * Demande de contact devenue prospect du CRM.
 * Données personnelles limitées au strict nécessaire, anonymisées après la durée de conservation.
 */
class Lead
{
    public const string ANONYMIZED = '[anonymisé]';

    private LeadStatus $status = LeadStatus::New;
    private ?\DateTimeImmutable $anonymizedAt = null;
    private \DateTimeImmutable $lastContactAt;

    /** @var list<object> */
    private array $events = [];

    private function __construct(
        private string $id,
        private string $reference,
        private string $fullName,
        private string $email,
        private ?string $phone,
        private ?string $company,
        private ProjectType $projectType,
        private ?BudgetRange $budgetRange,
        private ?Timeline $timeline,
        private string $message,
        private Locale $locale,
        private ?string $sourceUrl,
        private \DateTimeImmutable $consentAt,
        private \DateTimeImmutable $createdAt,
    ) {
        $this->lastContactAt = $createdAt;
    }

    public static function submit(
        LeadId $id,
        string $reference,
        ContactDetails $contact,
        ProjectBrief $brief,
        Locale $locale,
        ?string $sourceUrl,
        \DateTimeImmutable $now,
    ): self {
        $lead = new self(
            id: $id->value,
            reference: $reference,
            fullName: $contact->fullName,
            email: mb_strtolower($contact->email),
            phone: $contact->phone,
            company: $contact->company,
            projectType: $brief->type,
            budgetRange: $brief->budget,
            timeline: $brief->timeline,
            message: $brief->message,
            locale: $locale,
            sourceUrl: $sourceUrl,
            consentAt: $now,
            createdAt: $now,
        );
        $lead->events[] = new LeadSubmitted($id->value);

        return $lead;
    }

    /** Suivi commercial depuis le back-office. Ne compte pas comme un contact du prospect. */
    public function changeStatus(LeadStatus $status): void
    {
        $this->status = $status;
    }

    /** Efface les données personnelles ; conserve les informations utiles aux statistiques. */
    public function anonymize(\DateTimeImmutable $now): void
    {
        if (null !== $this->anonymizedAt) {
            return;
        }
        $this->fullName = self::ANONYMIZED;
        $this->email = self::ANONYMIZED;
        $this->phone = null;
        $this->company = null;
        $this->message = self::ANONYMIZED;
        $this->sourceUrl = null;
        $this->anonymizedAt = $now;
    }

    /** @return list<object> */
    public function pullEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    public function id(): LeadId
    {
        return LeadId::fromString($this->id);
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function company(): ?string
    {
        return $this->company;
    }

    public function projectType(): ProjectType
    {
        return $this->projectType;
    }

    public function budgetRange(): ?BudgetRange
    {
        return $this->budgetRange;
    }

    public function timeline(): ?Timeline
    {
        return $this->timeline;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function sourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function status(): LeadStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function consentAt(): \DateTimeImmutable
    {
        return $this->consentAt;
    }

    public function lastContactAt(): \DateTimeImmutable
    {
        return $this->lastContactAt;
    }

    public function anonymizedAt(): ?\DateTimeImmutable
    {
        return $this->anonymizedAt;
    }

    public function isAnonymized(): bool
    {
        return null !== $this->anonymizedAt;
    }
}

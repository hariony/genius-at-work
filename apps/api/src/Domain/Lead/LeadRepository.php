<?php

declare(strict_types=1);

namespace App\Domain\Lead;

interface LeadRepository
{
    public function save(Lead $lead): void;

    public function get(LeadId $id): ?Lead;

    /** Prochaine référence lisible de l'année : GAW-2026-00042. */
    public function nextReference(int $year): string;

    /**
     * Leads non anonymisés dont le dernier contact est antérieur à la date donnée.
     *
     * @return iterable<Lead>
     */
    public function findNotAnonymizedLastContactedBefore(\DateTimeImmutable $limit): iterable;
}

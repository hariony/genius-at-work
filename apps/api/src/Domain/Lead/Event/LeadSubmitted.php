<?php

declare(strict_types=1);

namespace App\Domain\Lead\Event;

/** Émis à la création d'un lead. Ne transporte que l'identifiant (aucune donnée personnelle dans la file). */
final readonly class LeadSubmitted
{
    public function __construct(public string $leadId)
    {
    }
}

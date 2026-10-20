<?php

declare(strict_types=1);

namespace App\Application\Notification;

use App\Domain\Lead\Lead;

interface LeadNotifier
{
    /** Prévient l'équipe d'une nouvelle demande. */
    public function notifyTeam(Lead $lead): void;

    /** Accusé de réception au prospect, dans sa langue. */
    public function acknowledge(Lead $lead): void;
}

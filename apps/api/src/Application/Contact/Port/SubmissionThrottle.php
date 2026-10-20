<?php

declare(strict_types=1);

namespace App\Application\Contact\Port;

/** Limite le nombre d'envois par client. */
interface SubmissionThrottle
{
    /** Consomme un envoi ; renvoie false si la limite est atteinte. */
    public function consume(string $clientKey): bool;
}

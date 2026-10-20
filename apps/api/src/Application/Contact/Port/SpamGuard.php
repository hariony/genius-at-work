<?php

declare(strict_types=1);

namespace App\Application\Contact\Port;

use App\Application\Contact\SubmitContactRequest\SubmitContactRequestCommand;

/** Détecte les envois automatisés évidents (champ piège, remplissage trop rapide). */
interface SpamGuard
{
    public function isSpam(SubmitContactRequestCommand $command): bool;
}

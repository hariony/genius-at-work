<?php

declare(strict_types=1);

namespace App\Application\Contact\Port;

/** Vérification côté serveur du jeton anti-robot (Cloudflare Turnstile). */
interface BotVerifier
{
    public function verify(string $token, ?string $clientIp): bool;
}

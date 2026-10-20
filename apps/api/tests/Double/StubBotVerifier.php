<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Application\Contact\Port\BotVerifier;

/** Accepte tout jeton sauf « invalid-token ». */
final class StubBotVerifier implements BotVerifier
{
    public const string REJECTED_TOKEN = 'invalid-token';

    public function verify(string $token, ?string $clientIp): bool
    {
        return self::REJECTED_TOKEN !== $token;
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Contact\Port\SpamGuard;
use App\Application\Contact\SubmitContactRequest\SubmitContactRequestCommand;
use Psr\Clock\ClockInterface;

/**
 * Champ piège rempli ou formulaire envoyé moins de 3 s après son affichage :
 * aucun humain ne remplit trois étapes aussi vite.
 */
final readonly class HoneypotSpamGuard implements SpamGuard
{
    private const int MIN_FILL_TIME_MS = 3000;

    public function __construct(private ClockInterface $clock)
    {
    }

    public function isSpam(SubmitContactRequestCommand $command): bool
    {
        if ('' !== trim($command->honeypot)) {
            return true;
        }

        $nowMs = (int) $this->clock->now()->format('Uv');

        return $nowMs - $command->startedAt < self::MIN_FILL_TIME_MS;
    }
}

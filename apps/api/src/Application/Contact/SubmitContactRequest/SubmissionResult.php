<?php

declare(strict_types=1);

namespace App\Application\Contact\SubmitContactRequest;

final readonly class SubmissionResult
{
    private function __construct(public string $reference, public bool $stored)
    {
    }

    public static function stored(string $reference): self
    {
        return new self($reference, true);
    }

    /** Envoi identifié comme automatisé : on répond comme si tout allait bien, sans rien enregistrer. */
    public static function discarded(string $reference): self
    {
        return new self($reference, false);
    }
}

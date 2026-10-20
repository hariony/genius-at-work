<?php

declare(strict_types=1);

namespace App\Domain\Content;

/** Numéro au format international : affiché tel quel, composé sans espaces (lien tel:). */
final readonly class Phone
{
    public const string PATTERN = '/^\+[0-9][0-9 ().-]{5,22}[0-9]$/';

    public string $e164;

    public function __construct(public string $display)
    {
        if (!preg_match(self::PATTERN, $display)) {
            throw new \InvalidArgumentException(\sprintf('Numéro invalide : %s.', $display));
        }
        $this->e164 = '+'.preg_replace('/\D/', '', $display);
    }
}

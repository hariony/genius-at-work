<?php

declare(strict_types=1);

namespace App\Domain\Content;

final readonly class SocialLink
{
    public function __construct(
        public string $label,
        public string $url,
    ) {
        if ('' === trim($label) || !str_starts_with($url, 'https://')) {
            throw new \InvalidArgumentException('Réseau social : libellé et adresse https obligatoires.');
        }
    }

    /** Identifiant stable pour le site (icône, clé de liste) : « LinkedIn » → « linkedin ». */
    public function id(): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($this->label)), '-');
    }
}

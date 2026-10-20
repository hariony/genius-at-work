<?php

declare(strict_types=1);

namespace App\Domain\Content;

/** Texte publié dans les deux langues du site ; aucune ne peut être vide. */
final readonly class LocalizedText
{
    public function __construct(
        public string $fr,
        public string $en,
    ) {
        if ('' === trim($fr) || '' === trim($en)) {
            throw new \InvalidArgumentException('Texte FR et EN obligatoires.');
        }
    }

    /** @param array{fr: string, en: string} $data */
    public static function fromArray(array $data): self
    {
        return new self($data['fr'], $data['en']);
    }

    /** @return array{fr: string, en: string} */
    public function toArray(): array
    {
        return ['fr' => $this->fr, 'en' => $this->en];
    }
}

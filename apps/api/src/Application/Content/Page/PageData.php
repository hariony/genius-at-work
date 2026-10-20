<?php

declare(strict_types=1);

namespace App\Application\Content\Page;

/** Textes d'une page, par langue, après normalisation ; erreurs par chemin (« fr.hero.title »). */
final readonly class PageData
{
    /**
     * @param array<string, mixed>  $fr
     * @param array<string, mixed>  $en
     * @param array<string, string> $errors
     */
    public function __construct(
        public array $fr,
        public array $en,
        public array $errors = [],
    ) {
    }

    public function isValid(): bool
    {
        return [] === $this->errors;
    }
}

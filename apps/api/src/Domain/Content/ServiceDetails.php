<?php

declare(strict_types=1);

namespace App\Domain\Content;

final readonly class ServiceDetails
{
    /**
     * @param list<string> $scopeFr
     * @param list<string> $scopeEn
     * @param list<string> $technologies
     * @param list<string> $relatedProjects slugs des projets mis en avant sur la page
     */
    public function __construct(
        public LocalizedText $title,
        public LocalizedText $summary,
        public LocalizedText $intro,
        public array $scopeFr,
        public array $scopeEn,
        public array $technologies,
        public array $relatedProjects,
    ) {
        if ([] === $scopeFr || [] === $scopeEn) {
            throw new \InvalidArgumentException('Périmètre FR et EN obligatoires.');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Content;

/** Tout ce qui se modifie dans la fiche d'un projet, validé d'un bloc. */
final readonly class ProjectDetails
{
    public const string SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * @param list<ProjectCategory> $categories
     * @param list<string>          $technologies
     */
    public function __construct(
        public string $slug,
        public string $title,
        public ?string $client,
        public ?ClientType $clientType,
        public ?int $periodStart,
        public ?int $periodEnd,
        public array $categories,
        public array $technologies,
        public ?string $url,
        public LocalizedText $summary,
        public Media $cover,
        public LocalizedText $coverAlt,
        public bool $featured,
        public bool $published,
    ) {
        if (!preg_match(self::SLUG_PATTERN, $slug) || \strlen($slug) > 80) {
            throw new \InvalidArgumentException('Adresse (slug) invalide.');
        }
        if ('' === trim($title)) {
            throw new \InvalidArgumentException('Titre obligatoire.');
        }
        if ([] === $categories) {
            throw new \InvalidArgumentException('Au moins une catégorie.');
        }
        if ((null === $periodStart) !== (null === $periodEnd)) {
            throw new \InvalidArgumentException('Période : début et fin, ou aucune des deux.');
        }
        if (null !== $periodStart && null !== $periodEnd && ($periodStart < 1990 || $periodEnd > 2100 || $periodStart > $periodEnd)) {
            throw new \InvalidArgumentException('Période incohérente.');
        }
        if (null !== $url && !preg_match('#^https?://#', $url)) {
            throw new \InvalidArgumentException('Adresse du site invalide.');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Content;

/** Réglages du site : identité, contact, réseaux sociaux et bureaux (dans l'ordre d'affichage). */
final readonly class SiteSettings
{
    /**
     * @param list<SocialLink> $socials
     * @param list<Office>     $offices
     */
    public function __construct(
        public string $name,
        public string $email,
        public array $socials,
        public array $offices,
    ) {
        if ('' === trim($name)) {
            throw new \InvalidArgumentException('Nom du site obligatoire.');
        }
        if (false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('E-mail de contact invalide.');
        }
        if ([] === $offices) {
            throw new \InvalidArgumentException('Au moins un bureau.');
        }
        $ids = array_map(static fn (Office $office): string => $office->id, $offices);
        if (\count($ids) !== \count(array_unique($ids))) {
            throw new \InvalidArgumentException('Deux bureaux ont le même identifiant.');
        }
    }
}

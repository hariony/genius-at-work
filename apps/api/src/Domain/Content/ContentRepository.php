<?php

declare(strict_types=1);

namespace App\Domain\Content;

/** Contenu éditorial du site, édité dans le back-office et lu par le site au build. */
interface ContentRepository
{
    public function settings(): ?SiteSettings;

    public function saveSettings(SiteSettings $settings, string $actor, \DateTimeImmutable $now): void;

    /** @return array<string, PageContent> textes des pages enregistrés, par identifiant */
    public function pages(): array;

    public function page(string $id): ?PageContent;

    public function savePage(PageContent $page): void;

    /** Un import initial (ex. « projects ») a-t-il déjà eu lieu ? Supprimer ensuite ne réimporte rien. */
    public function wasImported(string $content): bool;

    public function markImported(string $content, \DateTimeImmutable $now): void;
}

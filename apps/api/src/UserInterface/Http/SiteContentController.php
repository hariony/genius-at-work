<?php

declare(strict_types=1);

namespace App\UserInterface\Http;

use App\Application\Content\Page\PageCatalog;
use App\Application\Content\ProjectMapper;
use App\Application\Content\ServiceMapper;
use App\Application\Content\SettingsMapper;
use App\Domain\Content\ContentRepository;
use App\Domain\Content\Project;
use App\Domain\Content\ProjectRepository;
use App\Domain\Content\Service;
use App\Domain\Content\ServiceRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contenu éditorial lu par le site Astro (au build en production, à chaque page en local).
 * Uniquement du contenu destiné à être publié : aucune donnée personnelle, aucun brouillon.
 * Les images sont des chemins (/media/…) relatifs à l'API.
 */
#[AsController]
final readonly class SiteContentController
{
    public function __construct(
        private ContentRepository $content,
        private ProjectRepository $projects,
        private ServiceRepository $services,
        private ClockInterface $clock,
    ) {
    }

    #[Route('/api/v1/site-content', name: 'site_content', methods: ['GET', 'HEAD'])]
    public function __invoke(): JsonResponse
    {
        $settings = $this->content->settings();
        $projects = array_values(array_filter($this->projects->all(), static fn (Project $p): bool => $p->isPublished()));
        $slugs = array_map(static fn (Project $p): string => $p->slug(), $projects);

        $content = [
            'settings' => null === $settings ? null : SettingsMapper::toSite($settings, $this->clock->now()),
            'projects' => array_map(ProjectMapper::toSite(...), $projects),
            'services' => array_map(static fn (Service $s): array => ServiceMapper::toSite($s, $slugs), $this->services->all()),
            'pages' => $this->pages(),
        ];
        // Empreinte du contenu publié : le site en développement se recharge quand elle change.
        $version = substr(hash('xxh128', json_encode($content, \JSON_THROW_ON_ERROR)), 0, 16);

        $response = new JsonResponse(['version' => $version] + $content);
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }

    /** @return array<string, array{fr: array<string, mixed>, en: array<string, mixed>}|null> une entrée par page du catalogue */
    private function pages(): array
    {
        $stored = $this->content->pages();
        $pages = [];
        foreach (array_keys(PageCatalog::all()) as $id) {
            $page = $stored[$id] ?? null;
            $pages[$id] = null === $page ? null : ['fr' => $page->fr, 'en' => $page->en];
        }

        return $pages;
    }
}

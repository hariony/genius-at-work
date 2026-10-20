<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Application\Content\Page\PageCatalog;
use App\Application\Content\Page\PageData;
use App\Application\Content\Page\PageNormalizer;
use App\Domain\Content\ContentRepository;
use App\Domain\Content\PageContent;
use App\Domain\Content\Project;
use App\Domain\Content\ProjectRepository;
use App\Domain\Content\Service;
use App\Domain\Content\ServiceRepository;
use App\Domain\Content\ServiceSlug;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Import initial du contenu (apps/api/data/content-seed.json, images dans data/media).
 * N'écrit que les contenus absents : relancer l'import ne remplace jamais une modification faite
 * dans le back-office. Les projets ne sont importés qu'une fois : un projet supprimé ne revient pas.
 *
 * @phpstan-import-type SettingsData from SettingsMapper
 * @phpstan-import-type ProjectData from ProjectMapper
 * @phpstan-import-type ServiceData from ServiceMapper
 *
 * @phpstan-type Seed array{settings?: SettingsData, projects?: list<ProjectData>, services?: list<ServiceData>, pages?: array<string, array{fr: array<string, mixed>, en: array<string, mixed>}>}
 */
final readonly class ImportContent
{
    public const string ACTOR = 'import';

    public function __construct(
        private ContentRepository $content,
        private ProjectRepository $projects,
        private ServiceRepository $services,
        private MediaLibrary $media,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param Seed $seed
     *
     * @return array<string, bool> contenu → importé (true) ou déjà présent (false)
     */
    public function __invoke(array $seed, string $mediaDirectory): array
    {
        $now = $this->clock->now();
        $report = [];
        if (isset($seed['settings'])) {
            $report['settings'] = null === $this->content->settings();
            if ($report['settings']) {
                $this->content->saveSettings(SettingsMapper::fromArray($seed['settings']), self::ACTOR, $now);
            }
        }

        if (isset($seed['projects'])) {
            $report['projects'] = !$this->content->wasImported('projects');
            if ($report['projects']) {
                foreach ($seed['projects'] as $i => $data) {
                    if (null !== $this->projects->bySlug($data['slug'])) {
                        continue;
                    }
                    $cover = $this->media->importFile($mediaDirectory.'/'.$data['cover']);
                    $this->projects->save(new Project((string) new Ulid(), ProjectMapper::fromArray($data, $cover), $i + 1, $now, self::ACTOR));
                }
                $this->content->markImported('projects', $now);
            }
        }

        foreach ($seed['services'] ?? [] as $data) {
            $slug = ServiceSlug::from($data['slug']);
            $key = 'service:'.$slug->value;
            $report[$key] = null === $this->services->get($slug);
            if ($report[$key]) {
                $this->services->save(new Service($slug, ServiceMapper::fromArray($data), $now, self::ACTOR));
            }
        }

        foreach ($seed['pages'] ?? [] as $id => $texts) {
            $key = 'page:'.$id;
            $report[$key] = null === $this->content->page($id);
            if ($report[$key]) {
                $data = $this->checkedPage($id, $texts);
                $this->content->savePage(new PageContent($id, $data->fr, $data->en, $now, self::ACTOR));
            }
        }

        return $report;
    }

    /**
     * Le contenu initial doit correspondre exactement au catalogue : sinon l'import s'arrête.
     *
     * @param array{fr: array<string, mixed>, en: array<string, mixed>} $texts
     */
    private function checkedPage(string $id, array $texts): PageData
    {
        $page = PageCatalog::get($id) ?? throw new \InvalidArgumentException(\sprintf('Page inconnue dans le contenu initial : %s.', $id));
        $data = PageNormalizer::fromStored($page, $texts['fr'], $texts['en']);
        if (!$data->isValid()) {
            throw new \InvalidArgumentException(\sprintf('Contenu initial de « %s » invalide : %s', $id, implode(' ', array_map(static fn (string $path, string $message): string => $path.' : '.$message, array_keys($data->errors), $data->errors))));
        }

        return $data;
    }
}

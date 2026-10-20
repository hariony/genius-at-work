<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Domain\Content\LocalizedText;
use App\Domain\Content\Service;
use App\Domain\Content\ServiceDetails;

/**
 * Service ⇄ tableau : format d'import (apps/api/data/content-seed.json) et format lu par le site.
 *
 * @phpstan-type ServiceData array{slug: string, title: array{fr: string, en: string}, summary: array{fr: string, en: string}, intro: array{fr: string, en: string}, scope: array{fr: list<string>, en: list<string>}, technologies: list<string>, relatedProjects: list<string>}
 */
final class ServiceMapper
{
    /** @param ServiceData $data */
    public static function fromArray(array $data): ServiceDetails
    {
        return new ServiceDetails(
            title: LocalizedText::fromArray($data['title']),
            summary: LocalizedText::fromArray($data['summary']),
            intro: LocalizedText::fromArray($data['intro']),
            scopeFr: $data['scope']['fr'],
            scopeEn: $data['scope']['en'],
            technologies: $data['technologies'],
            relatedProjects: $data['relatedProjects'],
        );
    }

    /**
     * @param list<string> $publishedSlugs projets publiés : les autres ne sont pas proposés en lien
     *
     * @return array<string, mixed>
     */
    public static function toSite(Service $service, array $publishedSlugs): array
    {
        $details = $service->details();

        return [
            'slug' => $service->slug()->value,
            'title' => $details->title->toArray(),
            'summary' => $details->summary->toArray(),
            'intro' => $details->intro->toArray(),
            'scope' => ['fr' => $details->scopeFr, 'en' => $details->scopeEn],
            'technologies' => $details->technologies,
            'relatedProjects' => array_values(array_intersect($details->relatedProjects, $publishedSlugs)),
        ];
    }
}

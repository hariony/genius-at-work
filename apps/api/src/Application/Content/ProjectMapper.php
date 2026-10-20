<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Domain\Content\ClientType;
use App\Domain\Content\LocalizedText;
use App\Domain\Content\Media;
use App\Domain\Content\Project;
use App\Domain\Content\ProjectCategory;
use App\Domain\Content\ProjectDetails;

/**
 * Projet ⇄ tableau : format d'import (apps/api/data/content-seed.json, image = nom de fichier
 * dans data/media) et format lu par le site.
 *
 * @phpstan-type ProjectData array{slug: string, title: string, client: string|null, clientType: string|null, period: array{start: int, end: int}|null, categories: list<string>, technologies: list<string>, url: string|null, summary: array{fr: string, en: string}, cover: string, coverAlt: array{fr: string, en: string}, featured: bool, published: bool}
 */
final class ProjectMapper
{
    /** @param ProjectData $data */
    public static function fromArray(array $data, Media $cover): ProjectDetails
    {
        return new ProjectDetails(
            slug: $data['slug'],
            title: $data['title'],
            client: $data['client'],
            clientType: null === $data['clientType'] ? null : ClientType::from($data['clientType']),
            periodStart: $data['period']['start'] ?? null,
            periodEnd: $data['period']['end'] ?? null,
            categories: array_map(static fn (string $c): ProjectCategory => ProjectCategory::from($c), $data['categories']),
            technologies: $data['technologies'],
            url: $data['url'],
            summary: LocalizedText::fromArray($data['summary']),
            cover: $cover,
            coverAlt: LocalizedText::fromArray($data['coverAlt']),
            featured: $data['featured'],
            published: $data['published'],
        );
    }

    /** @return array<string, mixed> */
    public static function toSite(Project $project): array
    {
        $details = $project->details();

        return [
            'slug' => $details->slug,
            'title' => $details->title,
            'client' => $details->client,
            'clientType' => $details->clientType?->value,
            'period' => null === $details->periodStart ? null : ['start' => $details->periodStart, 'end' => $details->periodEnd],
            'categories' => array_map(static fn (ProjectCategory $c): string => $c->value, $details->categories),
            'technologies' => $details->technologies,
            'url' => $details->url,
            'summary' => $details->summary->toArray(),
            'cover' => self::image($details->cover),
            'coverAlt' => $details->coverAlt->toArray(),
            'featured' => $details->featured,
        ];
    }

    /** @return array{src: string, width: int, height: int} */
    public static function image(Media $media): array
    {
        return ['src' => $media->path(), 'width' => $media->width(), 'height' => $media->height()];
    }
}

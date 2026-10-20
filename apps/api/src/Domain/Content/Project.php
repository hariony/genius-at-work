<?php

declare(strict_types=1);

namespace App\Domain\Content;

/** Réalisation du portfolio. Ordre d'affichage par `position` ; non publiée = absente du site. */
class Project
{
    private string $slug = '';
    private string $title = '';
    private ?string $client = null;
    private ?ClientType $clientType = null;
    private ?int $periodStart = null;
    private ?int $periodEnd = null;
    /** @var list<string> */
    private array $categories = [];
    /** @var list<string> */
    private array $technologies = [];
    private ?string $url = null;
    /** @var array{fr: string, en: string} */
    private array $summary = ['fr' => '', 'en' => ''];
    private Media $cover;
    /** @var array{fr: string, en: string} */
    private array $coverAlt = ['fr' => '', 'en' => ''];
    private bool $featured = false;
    private bool $published = false;
    private \DateTimeImmutable $updatedAt;
    private string $updatedBy;

    public function __construct(
        private string $id,
        ProjectDetails $details,
        private int $position,
        \DateTimeImmutable $now,
        string $actor,
    ) {
        $this->update($details, $now, $actor);
    }

    public function update(ProjectDetails $details, \DateTimeImmutable $now, string $actor): void
    {
        $this->slug = $details->slug;
        $this->title = trim($details->title);
        $this->client = $details->client;
        $this->clientType = $details->clientType;
        $this->periodStart = $details->periodStart;
        $this->periodEnd = $details->periodEnd;
        $this->categories = array_map(static fn (ProjectCategory $c): string => $c->value, $details->categories);
        $this->technologies = $details->technologies;
        $this->url = $details->url;
        $this->summary = $details->summary->toArray();
        $this->cover = $details->cover;
        $this->coverAlt = $details->coverAlt->toArray();
        $this->featured = $details->featured;
        $this->published = $details->published;
        $this->touch($now, $actor);
    }

    public function moveTo(int $position, \DateTimeImmutable $now, string $actor): void
    {
        $this->position = $position;
        $this->touch($now, $actor);
    }

    public function details(): ProjectDetails
    {
        return new ProjectDetails(
            slug: $this->slug,
            title: $this->title,
            client: $this->client,
            clientType: $this->clientType,
            periodStart: $this->periodStart,
            periodEnd: $this->periodEnd,
            categories: array_map(static fn (string $c): ProjectCategory => ProjectCategory::from($c), $this->categories),
            technologies: $this->technologies,
            url: $this->url,
            summary: LocalizedText::fromArray($this->summary),
            cover: $this->cover,
            coverAlt: LocalizedText::fromArray($this->coverAlt),
            featured: $this->featured,
            published: $this->published,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function cover(): Media
    {
        return $this->cover;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    /** @return list<ProjectCategory> */
    public function categories(): array
    {
        return array_map(static fn (string $c): ProjectCategory => ProjectCategory::from($c), $this->categories);
    }

    public function position(): int
    {
        return $this->position;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updatedBy(): string
    {
        return $this->updatedBy;
    }

    private function touch(\DateTimeImmutable $now, string $actor): void
    {
        $this->updatedAt = $now;
        $this->updatedBy = $actor;
    }
}

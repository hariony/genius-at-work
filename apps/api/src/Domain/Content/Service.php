<?php

declare(strict_types=1);

namespace App\Domain\Content;

/** Page de service : adresse et ordre fixes (ServiceSlug), contenu modifiable. */
class Service
{
    /** @var array{fr: string, en: string} */
    private array $title;
    /** @var array{fr: string, en: string} */
    private array $summary;
    /** @var array{fr: string, en: string} */
    private array $intro;
    /** @var array{fr: list<string>, en: list<string>} */
    private array $scope;
    /** @var list<string> */
    private array $technologies;
    /** @var list<string> */
    private array $relatedProjects;
    private \DateTimeImmutable $updatedAt;
    private string $updatedBy;
    private string $slug;

    public function __construct(
        ServiceSlug $slug,
        ServiceDetails $details,
        \DateTimeImmutable $now,
        string $actor,
    ) {
        $this->slug = $slug->value;
        $this->update($details, $now, $actor);
    }

    public function update(ServiceDetails $details, \DateTimeImmutable $now, string $actor): void
    {
        $this->title = $details->title->toArray();
        $this->summary = $details->summary->toArray();
        $this->intro = $details->intro->toArray();
        $this->scope = ['fr' => $details->scopeFr, 'en' => $details->scopeEn];
        $this->technologies = $details->technologies;
        $this->relatedProjects = $details->relatedProjects;
        $this->updatedAt = $now;
        $this->updatedBy = $actor;
    }

    public function details(): ServiceDetails
    {
        return new ServiceDetails(
            title: LocalizedText::fromArray($this->title),
            summary: LocalizedText::fromArray($this->summary),
            intro: LocalizedText::fromArray($this->intro),
            scopeFr: $this->scope['fr'],
            scopeEn: $this->scope['en'],
            technologies: $this->technologies,
            relatedProjects: $this->relatedProjects,
        );
    }

    public function slug(): ServiceSlug
    {
        return ServiceSlug::from($this->slug);
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updatedBy(): string
    {
        return $this->updatedBy;
    }
}

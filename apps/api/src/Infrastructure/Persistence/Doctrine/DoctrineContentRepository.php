<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Content\ImportContent;
use App\Application\Content\SettingsMapper;
use App\Domain\Content\ContentRepository;
use App\Domain\Content\PageContent;
use App\Domain\Content\SiteSettings;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @phpstan-import-type SettingsData from SettingsMapper
 */
final readonly class DoctrineContentRepository implements ContentRepository
{
    private const string SETTINGS = 'settings';
    private const string IMPORTED = 'imported:';
    private const string PAGE = 'page:';

    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function settings(): ?SiteSettings
    {
        $document = $this->em->find(ContentDocument::class, self::SETTINGS);
        if (null === $document) {
            return null;
        }
        /** @var SettingsData $data */
        $data = $document->data();

        return SettingsMapper::fromArray($data);
    }

    public function saveSettings(SiteSettings $settings, string $actor, \DateTimeImmutable $now): void
    {
        $data = SettingsMapper::toArray($settings);
        $document = $this->em->find(ContentDocument::class, self::SETTINGS);
        if (null === $document) {
            $this->em->persist(new ContentDocument(self::SETTINGS, $data, $now, $actor));
        } else {
            $document->replace($data, $actor, $now);
        }
        $this->em->flush();
    }

    public function pages(): array
    {
        /** @var list<ContentDocument> $documents */
        $documents = $this->em->createQueryBuilder()
            ->select('d')
            ->from(ContentDocument::class, 'd')
            ->where('d.id LIKE :prefix')
            ->setParameter('prefix', self::PAGE.'%')
            ->getQuery()
            ->getResult();

        $pages = [];
        foreach ($documents as $document) {
            $page = self::toPage($document);
            $pages[$page->id] = $page;
        }

        return $pages;
    }

    public function page(string $id): ?PageContent
    {
        $document = $this->em->find(ContentDocument::class, self::PAGE.$id);

        return null === $document ? null : self::toPage($document);
    }

    public function savePage(PageContent $page): void
    {
        $data = ['fr' => $page->fr, 'en' => $page->en];
        $document = $this->em->find(ContentDocument::class, self::PAGE.$page->id);
        if (null === $document) {
            $this->em->persist(new ContentDocument(self::PAGE.$page->id, $data, $page->updatedAt, $page->updatedBy));
        } else {
            $document->replace($data, $page->updatedBy, $page->updatedAt);
        }
        $this->em->flush();
    }

    public function wasImported(string $content): bool
    {
        return null !== $this->em->find(ContentDocument::class, self::IMPORTED.$content);
    }

    public function markImported(string $content, \DateTimeImmutable $now): void
    {
        if (!$this->wasImported($content)) {
            $this->em->persist(new ContentDocument(self::IMPORTED.$content, [], $now, ImportContent::ACTOR));
            $this->em->flush();
        }
    }

    private static function toPage(ContentDocument $document): PageContent
    {
        /** @var array{fr: array<string, mixed>, en: array<string, mixed>} $data */
        $data = $document->data();

        return new PageContent(substr($document->id(), \strlen(self::PAGE)), $data['fr'], $data['en'], $document->updatedAt(), $document->updatedBy());
    }
}

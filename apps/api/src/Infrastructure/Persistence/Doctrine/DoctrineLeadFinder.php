<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Crm\LeadPage;
use App\Application\Crm\LeadSearch;
use App\Application\Crm\Port\LeadFinder;
use App\Domain\Lead\Lead;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

final readonly class DoctrineLeadFinder implements LeadFinder
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function search(LeadSearch $search): LeadPage
    {
        $qb = $this->em->createQueryBuilder()
            ->select('l')
            ->from(Lead::class, 'l')
            ->orderBy('l.createdAt', 'DESC')
            ->addOrderBy('l.reference', 'DESC');

        if (null !== $search->status) {
            $qb->andWhere('l.status = :status')->setParameter('status', $search->status);
        }
        if (null !== $search->projectType) {
            $qb->andWhere('l.projectType = :type')->setParameter('type', $search->projectType);
        }
        $query = trim((string) $search->query);
        if ('' !== $query) {
            $qb->andWhere('LOWER(l.reference) LIKE :q OR LOWER(l.fullName) LIKE :q OR l.email LIKE :q OR LOWER(l.company) LIKE :q')
                ->setParameter('q', '%'.addcslashes(mb_strtolower($query), '%_').'%');
        }

        $page = max(1, $search->page);
        $qb->setFirstResult(($page - 1) * $search->perPage)->setMaxResults($search->perPage);

        /** @var Paginator<Lead> $paginator */
        $paginator = new Paginator($qb->getQuery(), fetchJoinCollection: false);

        return new LeadPage(
            items: array_values(iterator_to_array($paginator)),
            total: \count($paginator),
            page: $page,
            perPage: $search->perPage,
        );
    }

    public function countByStatus(): array
    {
        /** @var list<array{status: \BackedEnum, total: int|string}> $rows */
        $rows = $this->em->createQueryBuilder()
            ->select('l.status AS status, COUNT(l.id) AS total')
            ->from(Lead::class, 'l')
            ->groupBy('l.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['status']->value] = (int) $row['total'];
        }

        return $counts;
    }

    public function countCreatedSince(\DateTimeImmutable $since): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(l.id)')
            ->from(Lead::class, 'l')
            ->where('l.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

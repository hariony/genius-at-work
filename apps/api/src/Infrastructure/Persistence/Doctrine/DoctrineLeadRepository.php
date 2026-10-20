<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Lead\Lead;
use App\Domain\Lead\LeadId;
use App\Domain\Lead\LeadRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineLeadRepository implements LeadRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function save(Lead $lead): void
    {
        $this->em->persist($lead);
        $this->em->flush();
    }

    public function get(LeadId $id): ?Lead
    {
        return $this->em->find(Lead::class, $id->value);
    }

    public function nextReference(int $year): string
    {
        $prefix = \sprintf('GAW-%d-', $year);
        /** @var string|null $last */
        $last = $this->em->createQueryBuilder()
            ->select('MAX(l.reference)')
            ->from(Lead::class, 'l')
            ->where('l.reference LIKE :prefix')
            ->setParameter('prefix', $prefix.'%')
            ->getQuery()
            ->getSingleScalarResult();

        // La contrainte d'unicité sur `reference` protège des envois simultanés.
        $number = null === $last ? 1 : ((int) substr($last, \strlen($prefix))) + 1;

        return $prefix.str_pad((string) $number, 5, '0', \STR_PAD_LEFT);
    }

    public function findNotAnonymizedLastContactedBefore(\DateTimeImmutable $limit): iterable
    {
        /** @var iterable<Lead> $leads */
        $leads = $this->em->createQueryBuilder()
            ->select('l')
            ->from(Lead::class, 'l')
            ->where('l.anonymizedAt IS NULL')
            ->andWhere('l.lastContactAt < :limit')
            ->setParameter('limit', $limit)
            ->getQuery()
            ->toIterable();

        return $leads;
    }
}

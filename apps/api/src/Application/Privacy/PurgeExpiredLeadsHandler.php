<?php

declare(strict_types=1);

namespace App\Application\Privacy;

use App\Domain\Lead\LeadRepository;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Anonymise (sans supprimer) les leads dont le dernier contact dépasse la durée de conservation
 * (3 ans par défaut, recommandation CNIL pour la prospection B2B). Opération idempotente.
 */
#[AsMessageHandler]
final readonly class PurgeExpiredLeadsHandler
{
    public function __construct(
        private LeadRepository $leads,
        private ClockInterface $clock,
        private LoggerInterface $logger,
        #[Autowire('%app.lead_retention%')]
        private string $retention,
    ) {
    }

    public function __invoke(PurgeExpiredLeads $message): int
    {
        $now = $this->clock->now();
        $limit = $now->modify('-'.$this->retention);
        $count = 0;

        foreach ($this->leads->findNotAnonymizedLastContactedBefore($limit) as $lead) {
            $lead->anonymize($now);
            $this->leads->save($lead);
            ++$count;
        }

        $this->logger->info('Leads anonymisés.', ['count' => $count]);

        return $count;
    }
}

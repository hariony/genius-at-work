<?php

declare(strict_types=1);

namespace App\Application\Crm\AnonymizeLead;

use App\Application\Crm\LeadNotFound;
use App\Domain\Lead\LeadId;
use App\Domain\Lead\LeadRepository;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

final readonly class AnonymizeLeadHandler
{
    public function __construct(
        private LeadRepository $leads,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(AnonymizeLeadCommand $command): void
    {
        $lead = $this->leads->get(LeadId::fromString($command->leadId))
            ?? throw LeadNotFound::withId($command->leadId);

        if ($lead->isAnonymized()) {
            return;
        }

        $lead->anonymize($this->clock->now());
        $this->leads->save($lead);

        $this->logger->notice('Lead anonymisé à la demande.', [
            'reference' => $lead->reference(),
            'actor' => $command->actor,
        ]);
    }
}

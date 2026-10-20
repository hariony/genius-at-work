<?php

declare(strict_types=1);

namespace App\Application\Crm\ChangeLeadStatus;

use App\Application\Crm\LeadNotFound;
use App\Domain\Lead\LeadId;
use App\Domain\Lead\LeadRepository;
use Psr\Log\LoggerInterface;

final readonly class ChangeLeadStatusHandler
{
    public function __construct(
        private LeadRepository $leads,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ChangeLeadStatusCommand $command): void
    {
        $lead = $this->leads->get(LeadId::fromString($command->leadId))
            ?? throw LeadNotFound::withId($command->leadId);

        $previous = $lead->status();
        if ($previous === $command->status) {
            return;
        }

        $lead->changeStatus($command->status);
        $this->leads->save($lead);

        $this->logger->info('Statut du lead modifié.', [
            'reference' => $lead->reference(),
            'from' => $previous->value,
            'to' => $command->status->value,
            'actor' => $command->actor,
        ]);
    }
}

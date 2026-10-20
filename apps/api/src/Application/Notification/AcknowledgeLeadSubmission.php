<?php

declare(strict_types=1);

namespace App\Application\Notification;

use App\Domain\Lead\Event\LeadSubmitted;
use App\Domain\Lead\LeadId;
use App\Domain\Lead\LeadRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class AcknowledgeLeadSubmission
{
    public function __construct(private LeadRepository $leads, private LeadNotifier $notifier)
    {
    }

    public function __invoke(LeadSubmitted $event): void
    {
        $lead = $this->leads->get(LeadId::fromString($event->leadId));
        if (null !== $lead && !$lead->isAnonymized()) {
            $this->notifier->acknowledge($lead);
        }
    }
}

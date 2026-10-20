<?php

declare(strict_types=1);

namespace App\Application\Contact\SubmitContactRequest;

use App\Application\Contact\Port\BotVerifier;
use App\Application\Contact\Port\EventPublisher;
use App\Application\Contact\Port\IdGenerator;
use App\Application\Contact\Port\SpamGuard;
use App\Application\Contact\Port\SubmissionThrottle;
use App\Domain\Lead\ContactDetails;
use App\Domain\Lead\Lead;
use App\Domain\Lead\LeadRepository;
use App\Domain\Lead\ProjectBrief;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;

/**
 * Orchestration d'une demande de contact :
 * limite d'envois → filtre anti-spam → vérification Turnstile → création du lead → événement.
 * Les journaux ne contiennent aucune donnée personnelle.
 */
final readonly class SubmitContactRequestHandler
{
    public function __construct(
        private SubmissionThrottle $throttle,
        private SpamGuard $spamGuard,
        private BotVerifier $botVerifier,
        private LeadRepository $leads,
        private IdGenerator $ids,
        private EventPublisher $events,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SubmitContactRequestCommand $command): SubmissionResult
    {
        $now = $this->clock->now();

        if (!$this->throttle->consume($command->clientIp ?? 'unknown')) {
            throw SubmissionRejected::rateLimited();
        }

        if ($this->spamGuard->isSpam($command)) {
            $this->logger->info('Demande de contact écartée par le filtre anti-spam.');

            return SubmissionResult::discarded(\sprintf('GAW-%d-%05d', (int) $now->format('Y'), random_int(1, 99999)));
        }

        if (!$this->botVerifier->verify($command->turnstileToken, $command->clientIp)) {
            throw SubmissionRejected::botCheckFailed();
        }

        $lead = Lead::submit(
            id: $this->ids->nextLeadId(),
            reference: $this->leads->nextReference((int) $now->format('Y')),
            contact: new ContactDetails($command->fullName, $command->email, $command->phone, $command->company),
            brief: new ProjectBrief($command->projectType, $command->message, $command->budgetRange, $command->timeline),
            locale: $command->locale,
            sourceUrl: $command->sourceUrl,
            now: $now,
        );
        $this->leads->save($lead);

        // La demande est enregistrée : un incident sur la file de notifications ne doit pas
        // faire croire au prospect que son envoi a échoué. Il est journalisé en critique
        // (alerte) et le lead reste visible dans l'admin.
        try {
            $this->events->publish(...$lead->pullEvents());
        } catch (\Throwable $exception) {
            $this->logger->critical('Notifications du lead non mises en file.', [
                'reference' => $lead->reference(),
                'exception' => $exception::class,
            ]);
        }

        $this->logger->info('Nouveau lead enregistré.', ['reference' => $lead->reference()]);

        return SubmissionResult::stored($lead->reference());
    }
}

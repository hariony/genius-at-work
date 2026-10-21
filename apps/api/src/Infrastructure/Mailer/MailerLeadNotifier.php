<?php

declare(strict_types=1);

namespace App\Infrastructure\Mailer;

use App\Application\Notification\LeadNotifier;
use App\Domain\Lead\Lead;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final readonly class MailerLeadNotifier implements LeadNotifier
{
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire('%env(MAILER_FROM)%')]
        private string $from,
        #[Autowire('%env(CONTACT_TEAM_EMAIL)%')]
        private string $teamEmail,
    ) {
    }

    public function notifyTeam(Lead $lead): void
    {
        $this->mailer->send(
            new TemplatedEmail()
                ->from(new Address($this->from, 'Genius At Work'))
                ->to($this->teamEmail)
                ->replyTo(new Address($lead->email(), $lead->fullName()))
                ->subject(\sprintf('[%s] Nouvelle demande : %s', $lead->reference(), $lead->fullName()))
                ->htmlTemplate('emails/team_new_lead.html.twig')
                ->textTemplate('emails/team_new_lead.txt.twig')
                ->context(['lead' => $lead]),
        );
    }

    /**
     * Envoyé à l'adresse saisie par le visiteur : aucun texte saisi par lui (nom, message) n'y
     * figure, pour que le formulaire ne puisse pas servir à envoyer du spam.
     */
    public function acknowledge(Lead $lead): void
    {
        $locale = $lead->locale()->value;
        $subject = 'fr' === $locale
            ? \sprintf('Votre demande %s a bien été reçue', $lead->reference())
            : \sprintf('We have received your request %s', $lead->reference());

        $this->mailer->send(
            new TemplatedEmail()
                ->from(new Address($this->from, 'Genius At Work'))
                ->to($lead->email())
                ->subject($subject)
                ->htmlTemplate(\sprintf('emails/acknowledgement.%s.html.twig', $locale))
                ->textTemplate(\sprintf('emails/acknowledgement.%s.txt.twig', $locale))
                ->context(['lead' => $lead]),
        );
    }
}

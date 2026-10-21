<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Application\Notification\LeadNotifier;
use App\Domain\Lead\ContactDetails;
use App\Domain\Lead\Lead;
use App\Domain\Lead\LeadId;
use App\Domain\Lead\Locale;
use App\Domain\Lead\ProjectBrief;
use App\Domain\Lead\ProjectType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mime\Email;

final class LeadNotifierTest extends KernelTestCase
{
    private const string SPAM = 'Gagnez 1000 € sur http://spam.example';

    /** L'accusé de réception part vers une adresse saisie : rien de ce que tape le visiteur n'y figure. */
    public function testAcknowledgementContainsNoVisitorText(): void
    {
        static::getContainer()->get(LeadNotifier::class)->acknowledge($this->lead());

        $email = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertSame('contact@genius-at-work.com', $email->getFrom()[0]->getAddress());
        self::assertSame('', $email->getTo()[0]->getName());
        self::assertStringContainsString('GAW-2026-00001', (string) $email->getTextBody());
        foreach ([(string) $email->getSubject(), (string) $email->getTextBody(), (string) $email->getHtmlBody()] as $part) {
            self::assertStringNotContainsString('Gagnez', $part);
            self::assertStringNotContainsString('spam.example', $part);
        }
    }

    public function testTeamNotificationRepliesToTheVisitor(): void
    {
        static::getContainer()->get(LeadNotifier::class)->notifyTeam($this->lead());

        $email = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertSame('contact@genius-at-work.com', $email->getFrom()[0]->getAddress());
        self::assertSame('visiteur@example.com', $email->getReplyTo()[0]->getAddress());
    }

    private function lead(): Lead
    {
        return Lead::submit(
            id: LeadId::fromString('01JABCDEFGHJKMNPQRSTVWXYZ0'),
            reference: 'GAW-2026-00001',
            contact: new ContactDetails(self::SPAM, 'visiteur@example.com', null, self::SPAM),
            brief: new ProjectBrief(ProjectType::WebApp, self::SPAM),
            locale: Locale::French,
            sourceUrl: '/contact/',
            now: new \DateTimeImmutable('2026-10-21 10:00:00'),
        );
    }
}

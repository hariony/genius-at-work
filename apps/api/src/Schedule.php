<?php

declare(strict_types=1);

namespace App;

use App\Application\Privacy\PurgeExpiredLeads;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

/** Tâches planifiées (worker : messenger:consume scheduler_default). */
#[AsSchedule]
final readonly class Schedule implements ScheduleProviderInterface
{
    public function __construct(private CacheInterface $cache)
    {
    }

    public function getSchedule(): SymfonySchedule
    {
        return new SymfonySchedule()
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true)
            // Anonymisation RGPD des leads expirés, chaque nuit à 3 h (heure de Paris).
            ->add(RecurringMessage::cron('0 3 * * *', new PurgeExpiredLeads(), new \DateTimeZone('Europe/Paris')));
    }
}

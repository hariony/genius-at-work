<?php

declare(strict_types=1);

namespace App\UserInterface\Admin;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/** Pages du back-office : données personnelles, donc jamais en cache ni indexées. */
#[AsEventListener]
final class AdminResponseHeaders
{
    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !str_starts_with($event->getRequest()->getPathInfo(), '/admin')) {
            return;
        }
        $headers = $event->getResponse()->headers;
        $headers->set('Cache-Control', 'no-store, private');
        $headers->set('X-Robots-Tag', 'noindex, nofollow');
    }
}

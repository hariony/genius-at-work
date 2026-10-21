<?php

declare(strict_types=1);

namespace App\UserInterface\Admin;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Le back-office n'existe que sur son propre domaine (ADMIN_HOST : office.genius-at-work.com en
 * production, office.localhost en local) : ailleurs, /admin répond 404. Sur ce domaine, la
 * racine mène au back-office. Passe avant le routeur et le pare-feu.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 256)]
final readonly class AdminHost
{
    public function __construct(
        #[Autowire('%env(ADMIN_HOST)%')]
        private string $adminHost,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        $isAdminPath = '/admin' === $path || str_starts_with($path, '/admin/');
        $onAdminHost = 0 === strcasecmp($request->getHost(), $this->adminHost);

        if ($isAdminPath && !$onAdminHost) {
            throw new NotFoundHttpException();
        }
        if ($onAdminHost && '/' === $path) {
            $event->setResponse(new RedirectResponse('/admin'));
        }
    }
}

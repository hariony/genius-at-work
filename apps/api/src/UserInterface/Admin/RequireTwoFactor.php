<?php

declare(strict_types=1);

namespace App\UserInterface\Admin;

use App\Infrastructure\Security\AdminUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Double authentification obligatoire : un compte connecté qui ne l'a pas encore activée ne voit
 * que la page d'activation (et peut se déconnecter). Passe après le pare-feu.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 0)]
final readonly class RequireTwoFactor
{
    private const array ALLOWED_ROUTES = [TwoFactorSetupController::ROUTE, 'admin_logout', 'admin_login'];

    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urls,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !str_starts_with($event->getRequest()->getPathInfo(), '/admin')) {
            return;
        }
        $user = $this->security->getUser();
        if (!$user instanceof AdminUser || $user->isTotpAuthenticationEnabled()) {
            return;
        }
        if (\in_array($event->getRequest()->attributes->get('_route'), self::ALLOWED_ROUTES, true)) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->urls->generate(TwoFactorSetupController::ROUTE)));
    }
}

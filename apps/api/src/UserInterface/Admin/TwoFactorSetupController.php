<?php

declare(strict_types=1);

namespace App\UserInterface\Admin;

use App\Infrastructure\Security\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Activation de la double authentification : la clé est gardée en session le temps que le
 * compte prouve, par un premier code, que son application est bien configurée.
 */
final class TwoFactorSetupController extends AbstractController
{
    public const string ROUTE = 'admin_2fa_setup';
    private const string PENDING_SECRET = 'admin_totp_pending_secret';

    public function __construct(
        private readonly TotpAuthenticatorInterface $totp,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/admin/double-authentification', name: self::ROUTE, methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        \assert($user instanceof AdminUser);
        if ($user->isTotpAuthenticationEnabled()) {
            return $this->redirectToRoute('admin_leads');
        }

        $session = $request->getSession();
        $secret = $session->get(self::PENDING_SECRET);
        if (!\is_string($secret)) {
            $secret = $this->totp->generateSecret();
            $session->set(self::PENDING_SECRET, $secret);
        }
        // Copie non enregistrée du compte, munie de la clé en attente : sert au QR code et au contrôle.
        $candidate = clone $user;
        $candidate->enableTotp($secret);

        $error = null;
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin-2fa-setup', $request->request->getString('_token'))) {
                $error = 'La page a expiré. Rechargez-la et réessayez.';
            } elseif (!$this->totp->checkCode($candidate, preg_replace('/\s+/', '', $request->request->getString('code')) ?? '')) {
                $error = 'Code incorrect. Vérifiez l’heure de votre téléphone et réessayez.';
            } else {
                $user->enableTotp($secret);
                $this->em->flush();
                $session->remove(self::PENDING_SECRET);
                $this->logger->notice('Double authentification activée.', ['actor' => $user->email()]);
                $this->addFlash('success', 'Double authentification activée : le code sera demandé à chaque connexion.');

                return $this->redirectToRoute('admin_leads');
            }
        }

        $qrCode = new Builder(writer: new SvgWriter(), writerOptions: [SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true], data: $this->totp->getQRContent($candidate), size: 180, margin: 0)
            ->build()
            ->getString();

        return $this->render('admin/two_factor_setup.html.twig', [
            'qr_code' => $qrCode,
            'secret' => $secret,
            'error' => $error,
        ], new Response(status: null === $error ? 200 : 422));
    }
}

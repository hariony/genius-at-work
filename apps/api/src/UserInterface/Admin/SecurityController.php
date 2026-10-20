<?php

declare(strict_types=1);

namespace App\UserInterface\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route('/admin/login', name: 'admin_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authentication): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('admin_leads');
        }

        return $this->render('admin/login.html.twig', [
            'last_username' => $authentication->getLastUsername(),
            'error' => $authentication->getLastAuthenticationError(),
        ]);
    }

    /** Intercepté par le pare-feu (security.yaml, firewalls.admin.logout). */
    #[Route('/admin/logout', name: 'admin_logout', methods: ['POST'])]
    public function logout(): never
    {
        throw new \LogicException('Intercepté par le pare-feu.');
    }
}

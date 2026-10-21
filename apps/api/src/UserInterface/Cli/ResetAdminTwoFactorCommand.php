<?php

declare(strict_types=1);

namespace App\UserInterface\Cli;

use App\Infrastructure\Security\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Téléphone perdu ou changé : retire la double authentification d'un compte. Elle sera
 * redemandée (nouveau QR code) juste après la prochaine connexion par mot de passe.
 */
#[AsCommand(name: 'app:admin:2fa-reset', description: 'Réinitialise la double authentification d\'un compte du back-office.')]
final readonly class ResetAdminTwoFactorCommand
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Adresse e-mail du compte')]
        string $email,
    ): int {
        $user = $this->em->getRepository(AdminUser::class)->findOneBy(['email' => mb_strtolower($email)]);
        if (null === $user) {
            $io->error('Aucun compte avec cette adresse.');

            return Command::FAILURE;
        }

        $user->resetTotp();
        $this->em->flush();
        $io->success(\sprintf('Double authentification réinitialisée pour %s : elle sera réactivée à la prochaine connexion.', $user->email()));

        return Command::SUCCESS;
    }
}

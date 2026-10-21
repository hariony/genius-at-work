<?php

declare(strict_types=1);

namespace App\UserInterface\Cli;

use App\Infrastructure\Security\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Change le mot de passe d'un compte du back-office existant. Le mot de passe est saisi masqué,
 * jamais passé en argument (il resterait dans l'historique du shell).
 */
#[AsCommand(name: 'app:admin:password', description: 'Change le mot de passe d\'un compte du back-office.')]
final readonly class ChangeAdminPasswordCommand
{
    private const int MIN_PASSWORD_LENGTH = 12;

    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
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

        /** @var string $password */
        $password = $io->askHidden(
            \sprintf('Nouveau mot de passe (%d caractères minimum)', self::MIN_PASSWORD_LENGTH),
            static function (mixed $value): string {
                if (!\is_string($value) || mb_strlen($value) < self::MIN_PASSWORD_LENGTH) {
                    throw new \RuntimeException('Mot de passe trop court.');
                }

                return $value;
            },
        );
        if ($password !== $io->askHidden('Confirmer le mot de passe')) {
            $io->error('Les deux saisies ne correspondent pas.');

            return Command::FAILURE;
        }

        $user->setPassword($this->hasher->hashPassword($user, $password));
        $this->em->flush();

        $io->success(\sprintf('Mot de passe changé : %s.', $user->email()));

        return Command::SUCCESS;
    }
}

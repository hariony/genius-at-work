<?php

declare(strict_types=1);

namespace App\UserInterface\Cli;

use App\Infrastructure\Security\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Crée un compte du back-office. Le mot de passe est saisi masqué, jamais passé en argument
 * (il resterait dans l'historique du shell).
 */
#[AsCommand(name: 'app:admin:create', description: 'Crée un compte administrateur du back-office.')]
final readonly class CreateAdminUserCommand
{
    private const int MIN_PASSWORD_LENGTH = 12;

    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Adresse e-mail, utilisée comme identifiant')]
        string $email,
    ): int {
        if (false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $io->error('Adresse e-mail invalide.');

            return Command::INVALID;
        }
        if (null !== $this->em->getRepository(AdminUser::class)->findOneBy(['email' => mb_strtolower($email)])) {
            $io->error('Un compte existe déjà avec cette adresse.');

            return Command::FAILURE;
        }

        /** @var string $password */
        $password = $io->askHidden(
            \sprintf('Mot de passe (%d caractères minimum)', self::MIN_PASSWORD_LENGTH),
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

        $user = new AdminUser((string) new Ulid(), $email, $this->clock->now());
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $this->em->persist($user);
        $this->em->flush();

        $io->success(\sprintf('Compte créé : %s. La double authentification sera activée à la première connexion.', $user->email()));

        return Command::SUCCESS;
    }
}

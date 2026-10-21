<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Compte du back-office. Créé uniquement en ligne de commande (app:admin:create). La double
 * authentification (code TOTP d'une application d'authentification) est obligatoire : tant
 * qu'elle n'est pas activée, le back-office mène à son activation.
 */
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    private string $password = '';
    private ?string $totpSecret = null;

    public function __construct(
        private string $id,
        private string $email,
        private \DateTimeImmutable $createdAt,
    ) {
        $this->email = mb_strtolower($email);
    }

    public function setPassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUserIdentifier(): string
    {
        \assert('' !== $this->email);

        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_ADMIN'];
    }

    /** Rien à effacer : aucun mot de passe en clair n'est conservé (méthode retirée en Symfony 8). */
    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return null !== $this->totpSecret;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /** Réglages standard (SHA-1, 30 s, 6 chiffres) : compris par toutes les applications. */
    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        return null === $this->totpSecret ? null : new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function enableTotp(string $secret): void
    {
        $this->totpSecret = $secret;
    }

    /** Téléphone perdu : l'activation sera redemandée à la prochaine connexion. */
    public function resetTotp(): void
    {
        $this->totpSecret = null;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Ne garde ni le hachage ni la clé TOTP dans la session sérialisée (Symfony 7.3+) : le compte
     * est rechargé depuis la base à chaque requête.
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'createdAt' => $this->createdAt,
            'password' => hash('crc32c', $this->password),
        ];
    }

    /** @param array{id: string, email: string, createdAt: \DateTimeImmutable, password: string} $data */
    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->createdAt = $data['createdAt'];
        $this->password = $data['password'];
    }
}

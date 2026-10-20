<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/** Compte du back-office. Créé uniquement en ligne de commande (app:admin:create). */
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    private string $password = '';

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

    /** Ne garde pas le hachage dans la session sérialisée (Symfony 7.3+). */
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

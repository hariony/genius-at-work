<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Infrastructure\Security\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ChangeAdminPasswordTest extends KernelTestCase
{
    private const string EMAIL = 'equipe@example.test';

    private CommandTester $tester;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $em = $this->em();
        $schema = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $user = new AdminUser('01JABCDEFGHJKMNPQRSTVWXYZ9', self::EMAIL, new \DateTimeImmutable());
        $user->setPassword($this->hasher()->hashPassword($user, 'ancien-mot-de-passe'));
        $em->persist($user);
        $em->flush();

        $this->tester = new CommandTester(new Application($kernel)->find('app:admin:password'));
    }

    public function testChangesThePasswordOfAnExistingAccount(): void
    {
        $this->tester->setInputs(['nouveau-mot-de-passe', 'nouveau-mot-de-passe']);

        self::assertSame(Command::SUCCESS, $this->tester->execute(['email' => self::EMAIL]));
        $user = $this->user();
        self::assertTrue($this->hasher()->isPasswordValid($user, 'nouveau-mot-de-passe'));
        self::assertFalse($this->hasher()->isPasswordValid($user, 'ancien-mot-de-passe'));
    }

    public function testRefusesAnUnknownAccount(): void
    {
        self::assertSame(Command::FAILURE, $this->tester->execute(['email' => 'inconnu@example.test']));
    }

    public function testKeepsThePasswordWhenTheConfirmationDiffers(): void
    {
        $this->tester->setInputs(['nouveau-mot-de-passe', 'autre-mot-de-passe']);

        self::assertSame(Command::FAILURE, $this->tester->execute(['email' => self::EMAIL]));
        self::assertTrue($this->hasher()->isPasswordValid($this->user(), 'ancien-mot-de-passe'));
    }

    private function user(): AdminUser
    {
        $this->em()->clear();
        $user = $this->em()->getRepository(AdminUser::class)->findOneBy(['email' => self::EMAIL]);
        self::assertNotNull($user);

        return $user;
    }

    private function em(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        return $em;
    }

    private function hasher(): UserPasswordHasherInterface
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        return $hasher;
    }
}

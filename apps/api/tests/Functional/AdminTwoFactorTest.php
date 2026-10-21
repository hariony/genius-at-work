<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Infrastructure\Security\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use OTPHP\TOTP;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Double authentification du back-office : obligatoire, activée à la première connexion. */
final class AdminTwoFactorTest extends WebTestCase
{
    private const string EMAIL = 'admin@example.com';
    private const string PASSWORD = 'test-password-not-secret';
    private const string TOTP_SECRET = 'JBSWY3DPEHPK3PXP';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        /** @var CacheItemPoolInterface $limits */
        $limits = static::getContainer()->get('cache.rate_limiter');
        $limits->clear();
        $schema = new SchemaTool($this->em());
        $metadata = $this->em()->getMetadataFactory()->getAllMetadata();
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);
    }

    public function testWrongCodeKeepsTheBackOfficeClosed(): void
    {
        $this->createAdmin(self::TOTP_SECRET);
        $this->logIn();
        $this->client->followRedirect();
        self::assertResponseRedirects('/admin/2fa');

        $this->client->request('GET', '/admin/content/projects');
        self::assertResponseRedirects('/admin/2fa');

        $this->client->request('GET', '/admin/2fa');
        $this->client->submitForm('Valider', ['_auth_code' => '000000' === $this->code() ? '111111' : '000000']);
        $this->client->followRedirect();
        self::assertSelectorTextContains('.flash--error', 'Code incorrect.');

        // Retour à la page demandée avant le code.
        $this->client->submitForm('Valider', ['_auth_code' => $this->code()]);
        self::assertResponseRedirects('/admin/content/projects');
    }

    public function testAnAccountWithoutTwoFactorMustActivateItFirst(): void
    {
        $this->createAdmin(null);
        $this->logIn();
        self::assertResponseRedirects('/admin/leads');

        $this->client->followRedirect();
        self::assertResponseRedirects('/admin/double-authentification');
        $crawler = $this->client->followRedirect();
        self::assertSelectorExists('.totp-qr svg');
        $secret = str_replace(' ', '', $crawler->filter('.totp-secret')->text());
        \assert('' !== $secret);

        $this->client->request('GET', '/admin/content/projects');
        self::assertResponseRedirects('/admin/double-authentification');
        $this->client->followRedirect();
        self::assertSame($secret, str_replace(' ', '', $this->client->getCrawler()->filter('.totp-secret')->text()), 'La clé reste la même pendant l’activation.');

        $this->client->submitForm('Activer', ['code' => '000000' === $this->code($secret) ? '111111' : '000000']);
        self::assertResponseStatusCodeSame(422);
        self::assertFalse($this->admin()->isTotpAuthenticationEnabled());

        $this->client->submitForm('Activer', ['code' => $this->code($secret)]);
        self::assertResponseRedirects('/admin/leads');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertTrue($this->admin()->isTotpAuthenticationEnabled());
    }

    public function testResetCommandAsksForANewActivation(): void
    {
        $this->createAdmin(self::TOTP_SECRET);
        $tester = new CommandTester(new Application(self::$kernel ?? throw new \LogicException())->find('app:admin:2fa-reset'));

        self::assertSame(Command::SUCCESS, $tester->execute(['email' => self::EMAIL]));
        self::assertFalse($this->admin()->isTotpAuthenticationEnabled());
        self::assertSame(Command::FAILURE, $tester->execute(['email' => 'inconnu@example.test']));
    }

    private function logIn(): void
    {
        $this->client->request('GET', '/admin/login');
        $this->client->submitForm('Se connecter', ['_username' => self::EMAIL, '_password' => self::PASSWORD]);
    }

    /** @param non-empty-string $secret */
    private function code(string $secret = self::TOTP_SECRET): string
    {
        return TOTP::createFromSecret($secret)->now();
    }

    private function createAdmin(?string $totpSecret): void
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $admin = new AdminUser('01JABCDEFGHJKMNPQRSTVWXYZA', self::EMAIL, new \DateTimeImmutable());
        $admin->setPassword($hasher->hashPassword($admin, self::PASSWORD));
        if (null !== $totpSecret) {
            $admin->enableTotp($totpSecret);
        }
        $this->em()->persist($admin);
        $this->em()->flush();
        $this->em()->clear();
    }

    private function admin(): AdminUser
    {
        $this->em()->clear();

        return $this->em()->getRepository(AdminUser::class)->findOneBy(['email' => self::EMAIL]) ?? throw new \LogicException();
    }

    private function em(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        return $em;
    }
}

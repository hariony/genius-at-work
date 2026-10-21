<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Application\Content\ImportContent;
use App\Infrastructure\Security\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Base vide, import du contenu initial et connexion au back-office.
 *
 * @phpstan-import-type Seed from ImportContent
 */
trait ContentTestTools
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $em = $this->em();
        $schema = new SchemaTool($em);
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);
    }

    /** @return array<string, bool> */
    private function import(): array
    {
        /** @var ImportContent $import */
        $import = static::getContainer()->get(ImportContent::class);
        $data = \dirname(__DIR__, 2).'/data';
        /** @var Seed $seed */
        $seed = json_decode((string) file_get_contents($data.'/content-seed.json'), true, flags: \JSON_THROW_ON_ERROR);

        return $import($seed, $data.'/media');
    }

    private function login(): void
    {
        $admin = new AdminUser('01JABCDEFGHJKMNPQRSTVWXYZA', 'admin@example.com', new \DateTimeImmutable());
        $admin->enableTotp('JBSWY3DPEHPK3PXP');
        $this->em()->persist($admin);
        $this->em()->flush();
        $this->client->loginUser($admin, 'admin');
    }

    /** @return array<string, mixed> */
    private function json(): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        return $data;
    }

    private function em(): EntityManagerInterface
    {
        /* @var EntityManagerInterface */
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}

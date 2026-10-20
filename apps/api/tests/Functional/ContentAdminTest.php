<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Content\ContentRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ContentAdminTest extends WebTestCase
{
    use ContentTestTools;

    public function testSiteContentIsEmptyBeforeTheImport(): void
    {
        $this->client->request('GET', '/api/v1/site-content');

        self::assertResponseIsSuccessful();
        $content = $this->json();
        self::assertNull($content['settings']);
        self::assertSame([], $content['projects']);
        self::assertSame([], $content['services']);
    }

    public function testImportFillsTheSiteContentAndNeverOverwrites(): void
    {
        $this->import();
        $this->client->request('GET', '/api/v1/site-content');

        /** @var array{settings: array{email: string, offices: list<array{utcLabel: string, phones: list<array{e164: string}>}>}} $content */
        $content = $this->json();
        self::assertSame('contact@genius-at-work.com', $content['settings']['email']);
        self::assertSame('UTC+3', $content['settings']['offices'][0]['utcLabel']);
        self::assertSame('+261348531120', $content['settings']['offices'][0]['phones'][0]['e164']);

        $this->login();
        $this->client->request('GET', '/admin/content/settings');
        $this->client->submitForm('Enregistrer', ['settings[email]' => 'bonjour@example.com']);
        self::assertSame([false], array_values(array_unique($this->import())), 'Un contenu présent n’est pas réimporté.');
        self::assertSame('bonjour@example.com', $this->content()->settings()?->email);
    }

    /**
     * Le site est construit en CI à partir de data/site-content.snapshot.json (sans base de données) :
     * l'instantané doit rester identique à ce que la route renvoie après l'import initial.
     * Pour le régénérer : UPDATE_SNAPSHOT=1 bin/phpunit --filter Snapshot.
     */
    public function testSnapshotMatchesTheImportedSiteContent(): void
    {
        $this->import();
        $this->client->request('GET', '/api/v1/site-content');
        $content = $this->json();
        unset($content['version']);
        $expected = json_encode($content, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR)."\n";

        $file = \dirname(__DIR__, 2).'/data/site-content.snapshot.json';
        if ('1' === getenv('UPDATE_SNAPSHOT')) {
            file_put_contents($file, $expected);
        }
        self::assertSame($expected, (string) @file_get_contents($file), 'Instantané périmé : UPDATE_SNAPSHOT=1 bin/phpunit --filter Snapshot');
    }

    public function testSettingsPageRequiresALogin(): void
    {
        $this->client->request('GET', '/admin/content/settings');

        self::assertResponseRedirects('/admin/login');
    }

    public function testValidSettingsAreSaved(): void
    {
        $this->import();
        $this->login();
        $this->client->request('GET', '/admin/content/settings');
        $this->client->submitForm('Enregistrer', [
            'settings[offices][0][phones]' => "+261 34 85 311 20\n+261 20 22 000 00",
            'settings[socials][2][label]' => 'Instagram',
            'settings[socials][2][url]' => 'https://www.instagram.com/example/',
        ]);

        self::assertResponseRedirects('/admin/content/settings');
        $settings = $this->content()->settings();
        self::assertNotNull($settings);
        self::assertCount(2, $settings->offices[0]->phones);
        self::assertSame('instagram', $settings->socials[2]->id());
    }

    public function testInvalidSettingsAreRefusedFieldByField(): void
    {
        $this->import();
        $this->login();
        $this->client->request('GET', '/admin/content/settings');
        $this->client->submitForm('Enregistrer', [
            'settings[email]' => 'pas-un-email',
            'settings[offices][0][phones]' => '034 85 311 20',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-email', 'Adresse e-mail invalide.');
        self::assertSelectorTextContains('#error-offices-0-phones', 'format international');
        self::assertSame('contact@genius-at-work.com', $this->content()->settings()?->email);
    }

    public function testAddingAnOfficeDoesNotSaveAnything(): void
    {
        $this->import();
        $this->login();
        $this->client->request('GET', '/admin/content/settings');
        $this->client->submitForm('+ Ajouter un bureau');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(2, 'section[aria-labelledby^="office-"]');
        self::assertCount(1, $this->content()->settings()->offices ?? []);
    }

    private function content(): ContentRepository
    {
        $this->em()->clear();

        /* @var ContentRepository */
        return static::getContainer()->get(ContentRepository::class);
    }
}

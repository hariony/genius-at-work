<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Content\ContentRepository;
use App\Domain\Content\PageContent;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PageAdminTest extends WebTestCase
{
    use ContentTestTools;

    public function testEveryPageIsPublishedToTheSite(): void
    {
        $this->import();
        $this->client->request('GET', '/api/v1/site-content');

        /** @var array{pages: array<string, array{fr: array<string, mixed>, en: array<string, mixed>}>} $content */
        $content = $this->json();
        self::assertCount(15, $content['pages']);
        self::assertSame('Mentions légales', $content['pages']['legal']['fr']['title']);
    }

    public function testPagesRequireALogin(): void
    {
        $this->client->request('GET', '/admin/content/pages/home');

        self::assertResponseRedirects('/admin/login');
    }

    public function testAllPagesAreListedAndOpen(): void
    {
        $this->import();
        $this->login();
        $crawler = $this->client->request('GET', '/admin/content/pages');
        self::assertSelectorCount(15, '.service-item');

        foreach ($crawler->filter('.service-item a')->links() as $link) {
            $this->client->click($link);
            self::assertResponseIsSuccessful();
        }
    }

    public function testTextsAreSavedPerLanguage(): void
    {
        $this->import();
        $this->login();
        $this->client->request('GET', '/admin/content/pages/home');
        $this->client->submitForm('Enregistrer', [
            'page[fr][hero][title]' => 'Nous construisons du solide',
            'page[_][sections][manifesto][stats][0][value]' => '16',
        ]);

        self::assertResponseRedirects('/admin/content/pages/home');
        self::assertSame('Nous construisons du solide', $this->text('home', 'fr', 'hero.title'));
        self::assertSame('We build digital products', $this->text('home', 'en', 'hero.title'));
        self::assertSame(16, $this->text('home', 'en', 'sections.manifesto.stats.0.value'), 'Valeur commune aux deux langues.');
    }

    public function testAMissingTranslationIsRefused(): void
    {
        $this->import();
        $this->login();
        $this->client->request('GET', '/admin/content/pages/about');
        $this->client->submitForm('Enregistrer', ['page[en][hero][title]' => '']);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-f-en-hero-title', 'anglais');
        self::assertSelectorExists('.error-summary a[href="#f-en-hero-title"]');
        self::assertNotSame('', $this->text('about', 'en', 'hero.title'));
    }

    public function testItemsCanBeAddedAndRemoved(): void
    {
        $this->import();
        $this->login();
        $this->client->request('GET', '/admin/content/pages/about');
        $this->client->submitForm('Enregistrer', [
            // Ligne vide proposée pour l'ajout (index 2), puis retrait du premier élément.
            'page[fr][values][items][2][title]' => 'Transparence',
            'page[fr][values][items][2][description]' => 'Des devis détaillés.',
            'page[en][values][items][2][title]' => 'Transparency',
            'page[en][values][items][2][description]' => 'Detailed quotes.',
            'page[fr][values][items][0][title]' => '',
            'page[fr][values][items][0][description]' => '',
            'page[en][values][items][0][title]' => '',
            'page[en][values][items][0][description]' => '',
        ]);

        self::assertResponseRedirects('/admin/content/pages/about');
        self::assertSame('Trust', $this->text('about', 'en', 'values.items.0.title'));
        self::assertSame('Transparency', $this->text('about', 'en', 'values.items.1.title'));
        self::assertNull($this->text('about', 'en', 'values.items.2.title'));
    }

    public function testLegalInformationCanBeCompleted(): void
    {
        $this->import();
        $this->login();
        $this->client->request('GET', '/admin/content/pages/legal');
        $this->client->submitForm('Enregistrer', ['page[_][publisherInfo][siret]' => '123 456 789 00012']);

        self::assertResponseRedirects('/admin/content/pages/legal');
        self::assertSame('123 456 789 00012', $this->text('legal', 'fr', 'publisherInfo.siret'));
        self::assertSame('', $this->text('legal', 'en', 'publisherInfo.rcs'), 'Champ facultatif resté vide.');
    }

    public function testUnknownPageIsNotFound(): void
    {
        $this->login();
        $this->client->request('GET', '/admin/content/pages/blog');

        self::assertResponseStatusCodeSame(404);
    }

    /** Valeur enregistrée au chemin « hero.title » (null si absente). */
    private function text(string $id, string $lang, string $path): mixed
    {
        $page = $this->page($id);
        $value = 'fr' === $lang ? $page->fr : $page->en;
        foreach (explode('.', $path) as $key) {
            $value = \is_array($value) ? ($value[$key] ?? null) : null;
        }

        return $value;
    }

    private function page(string $id): PageContent
    {
        $this->em()->clear();
        /** @var ContentRepository $content */
        $content = static::getContainer()->get(ContentRepository::class);
        $page = $content->page($id);
        self::assertNotNull($page);

        return $page;
    }
}

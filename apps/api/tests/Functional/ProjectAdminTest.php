<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Content\Project;
use App\Domain\Content\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProjectAdminTest extends WebTestCase
{
    use ContentTestTools;

    public function testImportedProjectsArePublishedWithTheirImages(): void
    {
        $this->import();
        $this->client->request('GET', '/api/v1/site-content');

        /** @var array{projects: list<array{slug: string, cover: array{src: string, width: int, height: int}}>, services: list<array{slug: string}>} $content */
        $content = $this->json();
        self::assertCount(15, $content['projects']);
        self::assertSame('magicstay', $content['projects'][0]['slug']);
        self::assertSame(['src' => '/media/magicstay.jpg', 'width' => 1270, 'height' => 950], $content['projects'][0]['cover']);
        self::assertCount(7, $content['services']);
        self::assertFileExists($this->mediaDir().'/magicstay.jpg');
    }

    public function testProjectPagesRequireALogin(): void
    {
        $this->client->request('GET', '/admin/content/projects');

        self::assertResponseRedirects('/admin/login');
    }

    public function testListShowsTheProjectsInOrder(): void
    {
        $this->import();
        $this->login();
        $this->client->request('GET', '/admin/content/projects');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(15, '.project-card');
        self::assertSelectorTextContains('.project-card:first-child .project-title', 'MagicStay');
    }

    public function testUnpublishedProjectLeavesTheSiteAndItsServices(): void
    {
        $this->import();
        $this->login();
        $project = $this->project('dialopro');
        $crawler = $this->client->request('GET', '/admin/content/projects/'.$project->id());
        $form = $crawler->selectButton('Enregistrer')->last()->form();
        $published = $form['project[published]'];
        self::assertInstanceOf(ChoiceFormField::class, $published);
        $published->untick();
        $form['project[title]'] = 'Dialopro (refonte)';
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/content/projects/'.$project->id());
        self::assertSame('Dialopro (refonte)', $this->project('dialopro')->title());

        $this->client->request('GET', '/api/v1/site-content');
        /** @var array{projects: list<array{slug: string}>, services: list<array{relatedProjects: list<string>}>} $content */
        $content = $this->json();
        self::assertNotContains('dialopro', array_column($content['projects'], 'slug'));
        self::assertNotContains('dialopro', $content['services'][0]['relatedProjects']);
    }

    public function testNewProjectWithAnUploadedImage(): void
    {
        $this->import();
        $this->login();
        $this->client->request('POST', '/admin/content/projects/new', [
            '_token' => $this->token('/admin/content/projects/new'),
            'project' => $this->validInput(),
        ], ['cover' => $this->upload('wellcoloc.jpg')]);

        $project = $this->project('outil-de-planning');
        self::assertResponseRedirects('/admin/content/projects/'.$project->id());
        self::assertSame(16, $project->position(), 'Ajouté en fin de portfolio.');
        self::assertFalse($project->isPublished());
        self::assertMatchesRegularExpression('/^[0-9a-z]{26}\.jpg$/', $project->cover()->fileName());
        self::assertFileExists($this->mediaDir().'/'.$project->cover()->fileName());
    }

    public function testAFileThatIsNotAnImageIsRefusedAndTheFormIsKept(): void
    {
        $this->import();
        $this->login();
        $fake = tempnam(sys_get_temp_dir(), 'gaw');
        file_put_contents($fake, '<?php echo "pas une image";');
        $this->client->request('POST', '/admin/content/projects/new', [
            '_token' => $this->token('/admin/content/projects/new'),
            'project' => $this->validInput(),
        ], ['cover' => new UploadedFile($fake, 'capture.jpg', 'image/jpeg', null, true)]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-cover', 'Format non accepté');
        self::assertInputValueSame('project[title]', 'Outil de planning');
        self::assertNull($this->projects()->bySlug('outil-de-planning'));
    }

    public function testMissingFieldsAreReportedOneByOne(): void
    {
        $this->import();
        $this->login();
        $this->client->request('POST', '/admin/content/projects/new', [
            '_token' => $this->token('/admin/content/projects/new'),
            'project' => ['title' => 'Sans rien', 'periodStart' => '2024', 'periodEnd' => '2020'],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('#error-categories');
        self::assertSelectorExists('#error-summaryFr');
        self::assertSelectorTextContains('#error-periodEnd', 'précède');
        self::assertSelectorTextContains('#error-cover', 'Ajoutez une capture');
    }

    public function testASlugCannotBeUsedTwice(): void
    {
        $this->import();
        $this->login();
        $this->client->request('POST', '/admin/content/projects/new', [
            '_token' => $this->token('/admin/content/projects/new'),
            'project' => ['slug' => 'magicstay', 'coverId' => 'seed-magicstay'] + $this->validInput(),
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('#error-slug', 'déjà utilisée');
    }

    public function testProjectsCanBeReordered(): void
    {
        $this->import();
        $this->login();
        $second = $this->projects()->all()[1];
        $this->client->request('POST', '/admin/content/projects/'.$second->id().'/move', [
            '_token' => $this->token('/admin/content/projects'),
            'direction' => 'up',
        ]);

        self::assertResponseRedirects('/admin/content/projects#project-'.$second->id());
        $order = array_map(static fn (Project $p): string => $p->slug(), $this->projects()->all());
        self::assertSame(['transparency-international', 'magicstay'], \array_slice($order, 0, 2));
        self::assertSame(range(1, 15), array_map(static fn (Project $p): int => $p->position(), $this->projects()->all()));
    }

    public function testDeletionNeedsTheTitleAndIsNotUndoneByTheImport(): void
    {
        $this->import();
        $this->login();
        $project = $this->project('magicstay');
        $this->client->request('GET', '/admin/content/projects/'.$project->id().'/delete');
        $this->client->submitForm('Supprimer définitivement', ['confirm_title' => 'Magic']);
        self::assertResponseStatusCodeSame(422);
        self::assertNotNull($this->projects()->bySlug('magicstay'));

        $this->client->submitForm('Supprimer définitivement', ['confirm_title' => 'MagicStay']);
        self::assertResponseRedirects('/admin/content/projects');
        self::assertNull($this->projects()->bySlug('magicstay'));

        $this->import();
        self::assertNull($this->projects()->bySlug('magicstay'), 'Un projet supprimé n’est pas réimporté.');
    }

    /** @return array<string, mixed> */
    private function validInput(): array
    {
        return [
            'title' => 'Outil de planning',
            'categories' => ['web-app'],
            'technologies' => 'Symfony, React, Symfony',
            'summary_fr' => 'Planification des équipes.',
            'summary_en' => 'Team scheduling.',
            'coverAlt_fr' => 'Écran de planning',
            'coverAlt_en' => 'Scheduling screen',
        ];
    }

    private function upload(string $seedImage): UploadedFile
    {
        $copy = tempnam(sys_get_temp_dir(), 'gaw');
        copy(\dirname(__DIR__, 2).'/data/media/'.$seedImage, $copy);

        return new UploadedFile($copy, $seedImage, 'image/jpeg', null, true);
    }

    private function token(string $page): string
    {
        return (string) $this->client->request('GET', $page)->filter('input[name="_token"]')->first()->attr('value');
    }

    private function project(string $slug): Project
    {
        $project = $this->projects()->bySlug($slug);
        self::assertNotNull($project);

        return $project;
    }

    private function projects(): ProjectRepository
    {
        $this->em()->clear();

        /* @var ProjectRepository */
        return static::getContainer()->get(ProjectRepository::class);
    }

    private function mediaDir(): string
    {
        /* @var string */
        return static::getContainer()->getParameter('app.media_dir');
    }
}

<?php

declare(strict_types=1);

namespace App\UserInterface\Admin\Content;

use App\Application\Content\Page\PageCatalog;
use App\Application\Content\Page\PageData;
use App\Application\Content\Page\PageDefinition;
use App\Application\Content\Page\PageNormalizer;
use App\Application\Content\UpdatePage;
use App\Domain\Content\ContentRepository;
use App\Domain\Content\PageContent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/** Textes des pages, des blocs communs et des pages juridiques (PageCatalog). */
final class PageController extends AbstractController
{
    private const string CSRF = 'content-page';

    public function __construct(private readonly ContentRepository $content)
    {
    }

    #[Route('/admin/content/pages', name: 'admin_pages', methods: ['GET'])]
    public function index(): Response
    {
        $stored = $this->content->pages();
        $groups = [];
        foreach (PageCatalog::all() as $page) {
            $groups[$page->group][] = ['page' => $page, 'content' => $stored[$page->id] ?? null];
        }

        return $this->render('admin/content/pages/index.html.twig', ['groups' => $groups]);
    }

    #[Route('/admin/content/pages/{id}', name: 'admin_page_edit', requirements: ['id' => '[a-z-]+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, string $id, UpdatePage $update): Response
    {
        $page = PageCatalog::get($id) ?? throw new NotFoundHttpException('Page inconnue.');
        $stored = $this->content->page($id);

        if (!$request->isMethod('POST')) {
            $data = null === $stored
                ? PageNormalizer::normalize($page, [], [], [])
                : PageNormalizer::fromStored($page, $stored->fr, $stored->en);

            // À l'ouverture, seules les erreurs du contenu enregistré comptent (aucune pour une page neuve).
            return $this->show($page, $stored, new PageData($data->fr, $data->en, null === $stored ? [] : $data->errors));
        }

        $input = $request->request->all('page');
        $data = PageNormalizer::normalize($page, self::arr($input, 'fr'), self::arr($input, 'en'), self::arr($input, '_'));
        if (!$this->isCsrfTokenValid(self::CSRF, $request->request->getString('_token'))) {
            $this->addFlash('error', 'La page a expiré : rechargez-la, puis enregistrez de nouveau.');

            return $this->show($page, $stored, $data, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!$data->isValid()) {
            $this->addFlash('error', \sprintf('%d champ%s à corriger.', \count($data->errors), \count($data->errors) > 1 ? 's' : ''));

            return $this->show($page, $stored, $data, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $update($page, $data, $this->getUser()?->getUserIdentifier() ?? 'inconnu');
        $this->addFlash('success', \sprintf('« %s » enregistré. En ligne à la prochaine publication du site.', $page->title));

        return $this->redirectToRoute('admin_page_edit', ['id' => $id]);
    }

    private function show(PageDefinition $page, ?PageContent $stored, PageData $data, int $status = Response::HTTP_OK): Response
    {
        $nodes = PageFormView::build($page, $data);

        return $this->render('admin/content/pages/edit.html.twig', [
            'page' => $page,
            'stored' => $stored,
            'nodes' => $nodes,
            'field_errors' => PageFormView::errorSummary($nodes),
            'list_errors' => array_filter($data->errors, static fn (string $key): bool => str_starts_with($key, 'list.'), \ARRAY_FILTER_USE_KEY),
            'csrf_id' => self::CSRF,
        ], new Response(status: $status));
    }

    /**
     * @param array<mixed> $input
     *
     * @return array<mixed>
     */
    private static function arr(array $input, string $key): array
    {
        return \is_array($input[$key] ?? null) ? $input[$key] : [];
    }
}

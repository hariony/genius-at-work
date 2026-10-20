<?php

declare(strict_types=1);

namespace App\UserInterface\Admin\Content;

use App\Application\Content\DeleteProject;
use App\Application\Content\MediaLibrary;
use App\Application\Content\MediaRejected;
use App\Application\Content\MoveProject;
use App\Application\Content\SaveProject;
use App\Domain\Content\ClientType;
use App\Domain\Content\Media;
use App\Domain\Content\MediaRepository;
use App\Domain\Content\Project;
use App\Domain\Content\ProjectCategory;
use App\Domain\Content\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

/** Portfolio : liste ordonnée, création, modification, suppression des projets. */
final class ProjectController extends AbstractController
{
    private const string CSRF = 'content-project';

    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly MediaRepository $media,
    ) {
    }

    #[Route('/admin/content/projects', name: 'admin_projects', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/content/projects/index.html.twig', [
            'projects' => $this->projects->all(),
            'csrf_id' => self::CSRF,
        ]);
    }

    #[Route('/admin/content/projects/new', name: 'admin_project_new', methods: ['GET', 'POST'])]
    public function create(Request $request, SaveProject $save, MediaLibrary $library): Response
    {
        return $this->edit($request, null, $save, $library);
    }

    #[Route('/admin/content/projects/{id}', name: 'admin_project_edit', requirements: ['id' => Requirement::UID_BASE32], methods: ['GET', 'POST'])]
    public function update(Request $request, string $id, SaveProject $save, MediaLibrary $library): Response
    {
        return $this->edit($request, $this->find($id), $save, $library);
    }

    #[Route('/admin/content/projects/{id}/move', name: 'admin_project_move', requirements: ['id' => Requirement::UID_BASE32], methods: ['POST'])]
    public function move(Request $request, string $id, MoveProject $move): Response
    {
        if ($this->isCsrfTokenValid(self::CSRF, $request->request->getString('_token'))) {
            $move($id, 'up' === $request->request->getString('direction') ? -1 : 1, $this->actor());
        }

        return $this->redirect($this->generateUrl('admin_projects').'#project-'.$id);
    }

    #[Route('/admin/content/projects/{id}/delete', name: 'admin_project_delete', requirements: ['id' => Requirement::UID_BASE32], methods: ['GET', 'POST'])]
    public function delete(Request $request, string $id, DeleteProject $delete): Response
    {
        $project = $this->find($id);
        $error = null;
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(self::CSRF, $request->request->getString('_token'))) {
                $error = 'La page a expiré : rechargez-la, puis recommencez.';
            } elseif (trim($request->request->getString('confirm_title')) !== $project->title()) {
                $error = 'Le nom saisi ne correspond pas au projet.';
            } else {
                $delete($id, $this->actor());
                $this->addFlash('success', \sprintf('« %s » a été supprimé. Il disparaîtra du site à la prochaine publication.', $project->title()));

                return $this->redirectToRoute('admin_projects');
            }
        }

        return $this->render('admin/content/projects/delete.html.twig', [
            'project' => $project,
            'error' => $error,
            'csrf_id' => self::CSRF,
        ], new Response(status: null === $error ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    private function edit(Request $request, ?Project $project, SaveProject $save, MediaLibrary $library): Response
    {
        if (!$request->isMethod('POST')) {
            $form = null === $project ? ProjectForm::empty() : ProjectForm::fromProject($project);

            return $this->show($project, $form, $this->media->get($form->coverId));
        }

        // Au-delà de post_max_size, PHP ne transmet plus aucun champ (ni le jeton) : on le dit clairement.
        if ([] === $request->request->all() && $request->server->getInt('CONTENT_LENGTH') > 0) {
            $this->addFlash('error', 'Envoi trop lourd : l’image ne doit pas dépasser 10 Mo.');

            return $this->redirect($request->getRequestUri());
        }

        $form = ProjectForm::fromInput($request->request->all('project'));
        if (null === $project) {
            $form->suggestSlug();
        }
        if (!$this->isCsrfTokenValid(self::CSRF, $request->request->getString('_token'))) {
            $this->addFlash('error', 'La page a expiré : rechargez-la, puis enregistrez de nouveau.');

            return $this->show($project, $form, $this->media->get($form->coverId), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $uploadError = null;
        $file = $request->files->get('cover');
        if ($file instanceof UploadedFile) {
            try {
                $form->coverId = $library->upload($this->uploadedPath($file), $file->getClientOriginalName(), $this->actor())->id();
            } catch (MediaRejected $e) {
                $uploadError = $e->getMessage();
            }
        }
        $cover = $this->media->get($form->coverId);

        $valid = $form->validate($cover);
        if (null !== $uploadError) {
            $form->errors['cover'] = $uploadError;
        }
        if (!$valid || null !== $uploadError || null === $cover) {
            $this->addFlash('error', 'Certains champs sont à corriger.');

            return $this->show($project, $form, $cover, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $saved = $save($project?->id(), $form->toDetails($cover), $this->actor());
        } catch (\DomainException $e) {
            $form->errors['slug'] = $e->getMessage();
            $this->addFlash('error', 'Certains champs sont à corriger.');

            return $this->show($project, $form, $cover, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->addFlash('success', \sprintf(
            '« %s » enregistré. %s',
            $saved->title(),
            $saved->isPublished() ? 'Il sera en ligne à la prochaine publication du site.' : 'Brouillon : il reste masqué sur le site.',
        ));

        return $this->redirectToRoute('admin_project_edit', ['id' => $saved->id()]);
    }

    /** Fichier reçu : refusé avec un message clair si PHP l'a déjà rejeté (taille, envoi interrompu). */
    private function uploadedPath(UploadedFile $file): string
    {
        return match ($file->getError()) {
            \UPLOAD_ERR_OK => $file->getPathname(),
            \UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE => throw new MediaRejected('Image trop lourde : 10 Mo maximum.'),
            \UPLOAD_ERR_PARTIAL => throw new MediaRejected('Envoi interrompu : réessayez.'),
            default => throw new MediaRejected('L’image n’a pas pu être reçue : réessayez.'),
        };
    }

    private function show(?Project $project, ProjectForm $form, ?Media $cover, int $status = Response::HTTP_OK): Response
    {
        return $this->render('admin/content/projects/edit.html.twig', [
            'project' => $project,
            'form' => $form,
            'cover' => $cover,
            'categories' => ProjectCategory::cases(),
            'client_types' => ClientType::cases(),
            'max_upload_mb' => MediaLibrary::MAX_BYTES / 1048576,
            'csrf_id' => self::CSRF,
        ], new Response(status: $status));
    }

    private function find(string $id): Project
    {
        return $this->projects->get($id) ?? throw new NotFoundHttpException('Projet introuvable.');
    }

    private function actor(): string
    {
        return $this->getUser()?->getUserIdentifier() ?? 'inconnu';
    }
}

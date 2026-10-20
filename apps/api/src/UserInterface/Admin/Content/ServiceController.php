<?php

declare(strict_types=1);

namespace App\UserInterface\Admin\Content;

use App\Application\Content\UpdateService;
use App\Domain\Content\ProjectRepository;
use App\Domain\Content\Service;
use App\Domain\Content\ServiceRepository;
use App\Domain\Content\ServiceSlug;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/** Les pages de services : adresses fixes, textes modifiables. */
final class ServiceController extends AbstractController
{
    private const string CSRF = 'content-service';

    public function __construct(private readonly ServiceRepository $services)
    {
    }

    #[Route('/admin/content/services', name: 'admin_services', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/content/services/index.html.twig', ['services' => $this->services->all()]);
    }

    #[Route('/admin/content/services/{slug}', name: 'admin_service_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $slug, ProjectRepository $projects, UpdateService $update): Response
    {
        $service = $this->find($slug);
        if (!$request->isMethod('POST')) {
            return $this->show($service, ServiceForm::fromDetails($service->details()), $projects);
        }

        $form = ServiceForm::fromInput($request->request->all('service'));
        if (!$this->isCsrfTokenValid(self::CSRF, $request->request->getString('_token'))) {
            $this->addFlash('error', 'La page a expiré : rechargez-la, puis enregistrez de nouveau.');

            return $this->show($service, $form, $projects, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!$form->validate()) {
            $this->addFlash('error', 'Certains champs sont à corriger.');

            return $this->show($service, $form, $projects, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $update($service->slug(), $form->toDetails(), $this->getUser()?->getUserIdentifier() ?? 'inconnu');
        $this->addFlash('success', 'Service enregistré. Il sera en ligne à la prochaine publication du site.');

        return $this->redirectToRoute('admin_service_edit', ['slug' => $slug]);
    }

    private function show(Service $service, ServiceForm $form, ProjectRepository $projects, int $status = Response::HTTP_OK): Response
    {
        return $this->render('admin/content/services/edit.html.twig', [
            'service' => $service,
            'form' => $form,
            'projects' => $projects->all(),
            'csrf_id' => self::CSRF,
        ], new Response(status: $status));
    }

    private function find(string $slug): Service
    {
        $known = ServiceSlug::tryFrom($slug);

        return (null === $known ? null : $this->services->get($known)) ?? throw new NotFoundHttpException('Service introuvable.');
    }
}

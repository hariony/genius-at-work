<?php

declare(strict_types=1);

namespace App\UserInterface\Admin\Content;

use App\Application\Content\UpdateSettings;
use App\Domain\Content\ContentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Réglages du site : nom, e-mail, réseaux sociaux, bureaux. */
final class SettingsController extends AbstractController
{
    private const string CSRF = 'content-settings';

    #[Route('/admin/content/settings', name: 'admin_settings', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, ContentRepository $content, UpdateSettings $update): Response
    {
        if (!$request->isMethod('POST')) {
            return $this->show(SettingsForm::fromSettings($content->settings()));
        }

        $form = SettingsForm::fromInput($request->request->all('settings'));
        if (!$this->isCsrfTokenValid(self::CSRF, $request->request->getString('_token'))) {
            $this->addFlash('error', 'La page a expiré : rechargez-la, puis enregistrez de nouveau.');

            return $this->show($form, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Ajouter ou retirer un bureau ne fait que réafficher le formulaire, sans rien enregistrer.
        $action = $request->request->getString('action');
        if ('add_office' === $action) {
            $form->addOffice();

            return $this->show($form);
        }
        if (str_starts_with($action, 'remove_office:')) {
            $form->removeOffice((int) substr($action, \strlen('remove_office:')));

            return $this->show($form);
        }

        if (!$form->validate()) {
            $this->addFlash('error', 'Certains champs sont à corriger.');

            return $this->show($form, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $update($form->toSettings(), $this->getUser()?->getUserIdentifier() ?? 'inconnu');
        $this->addFlash('success', 'Réglages enregistrés. Ils seront en ligne à la prochaine publication du site.');

        return $this->redirectToRoute('admin_settings');
    }

    private function show(SettingsForm $form, int $status = Response::HTTP_OK): Response
    {
        return $this->render('admin/content/settings.html.twig', [
            'form' => $form,
            'time_zones' => \DateTimeZone::listIdentifiers(),
            'csrf_id' => self::CSRF,
        ], new Response(status: $status));
    }
}

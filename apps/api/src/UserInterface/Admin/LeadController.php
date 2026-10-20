<?php

declare(strict_types=1);

namespace App\UserInterface\Admin;

use App\Application\Crm\AnonymizeLead\AnonymizeLeadCommand;
use App\Application\Crm\AnonymizeLead\AnonymizeLeadHandler;
use App\Application\Crm\ChangeLeadStatus\ChangeLeadStatusCommand;
use App\Application\Crm\ChangeLeadStatus\ChangeLeadStatusHandler;
use App\Application\Crm\LeadSearch;
use App\Application\Crm\Port\LeadFinder;
use App\Domain\Lead\Lead;
use App\Domain\Lead\LeadId;
use App\Domain\Lead\LeadRepository;
use App\Domain\Lead\LeadStatus;
use App\Domain\Lead\ProjectType;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/** Back-office des demandes de contact : liste, fiche, suivi du statut, anonymisation. */
final class LeadController extends AbstractController
{
    private const string ID = '[0-9A-HJKMNP-TV-Z]{26}';

    public function __construct(
        private readonly LeadFinder $finder,
        private readonly LeadRepository $leads,
        private readonly ClockInterface $clock,
        #[Autowire('%app.lead_retention%')]
        private readonly string $retention,
    ) {
    }

    #[Route('/admin', name: 'admin_home', methods: ['GET'])]
    public function home(): RedirectResponse
    {
        return $this->redirectToRoute('admin_leads');
    }

    #[Route('/admin/leads', name: 'admin_leads', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $status = LeadStatus::tryFrom($request->query->getString('status'));
        $type = ProjectType::tryFrom($request->query->getString('type'));
        $query = mb_substr(trim($request->query->getString('q')), 0, 100);

        $page = $this->finder->search(new LeadSearch(
            status: $status,
            projectType: $type,
            query: $query,
            page: max(1, $request->query->getInt('page', 1)),
        ));
        $counts = $this->finder->countByStatus();

        return $this->render('admin/leads/index.html.twig', [
            'page' => $page,
            'counts' => $counts,
            'total' => array_sum($counts),
            'received_last_30_days' => $this->finder->countCreatedSince($this->clock->now()->modify('-30 days')),
            'statuses' => LeadStatus::cases(),
            'project_types' => ProjectType::cases(),
            'filters' => ['status' => $status, 'type' => $type, 'q' => $query],
        ]);
    }

    #[Route('/admin/leads/{id}', name: 'admin_lead_show', requirements: ['id' => self::ID], methods: ['GET'])]
    public function show(string $id): Response
    {
        $lead = $this->lead($id);

        return $this->render('admin/leads/show.html.twig', [
            'lead' => $lead,
            'statuses' => LeadStatus::cases(),
            'anonymized_on' => $lead->lastContactAt()->modify('+'.$this->retention),
        ]);
    }

    #[Route('/admin/leads/{id}/status', name: 'admin_lead_status', requirements: ['id' => self::ID], methods: ['POST'])]
    public function changeStatus(string $id, Request $request, ChangeLeadStatusHandler $handler): RedirectResponse
    {
        $lead = $this->lead($id);
        $status = LeadStatus::tryFrom($request->request->getString('status'));
        if (!$this->isCsrfTokenValid('lead-status-'.$id, $request->request->getString('_token')) || null === $status) {
            $this->addFlash('error', 'La modification n’a pas été enregistrée. Rechargez la page et réessayez.');

            return $this->redirectToRoute('admin_lead_show', ['id' => $id]);
        }

        $handler(new ChangeLeadStatusCommand($id, $status, $this->actor()));
        $this->addFlash('success', \sprintf('%s : statut mis à jour.', $lead->reference()));

        return $this->redirectToRoute('admin_lead_show', ['id' => $id]);
    }

    #[Route('/admin/leads/{id}/anonymize', name: 'admin_lead_anonymize', requirements: ['id' => self::ID], methods: ['GET', 'POST'])]
    public function anonymize(string $id, Request $request, AnonymizeLeadHandler $handler): Response
    {
        $lead = $this->lead($id);
        if ($lead->isAnonymized()) {
            return $this->redirectToRoute('admin_lead_show', ['id' => $id]);
        }

        if ($request->isMethod('POST')) {
            $confirmed = $lead->reference() === trim($request->request->getString('confirm_reference'));
            if ($this->isCsrfTokenValid('lead-anonymize-'.$id, $request->request->getString('_token')) && $confirmed) {
                $handler(new AnonymizeLeadCommand($id, $this->actor()));
                $this->addFlash('success', \sprintf('%s : données personnelles effacées.', $lead->reference()));

                return $this->redirectToRoute('admin_lead_show', ['id' => $id]);
            }
            $this->addFlash('error', 'Saisissez exactement la référence de la demande pour confirmer.');
        }

        return $this->render('admin/leads/anonymize.html.twig', ['lead' => $lead]);
    }

    private function lead(string $id): Lead
    {
        return $this->leads->get(LeadId::fromString($id)) ?? throw new NotFoundHttpException('Demande introuvable.');
    }

    private function actor(): string
    {
        return $this->getUser()?->getUserIdentifier() ?? 'inconnu';
    }
}

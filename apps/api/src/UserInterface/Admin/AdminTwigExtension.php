<?php

declare(strict_types=1);

namespace App\UserInterface\Admin;

use App\Domain\Content\ClientType;
use App\Domain\Content\ProjectCategory;
use App\Domain\Lead\BudgetRange;
use App\Domain\Lead\LeadStatus;
use App\Domain\Lead\Locale;
use App\Domain\Lead\ProjectType;
use App\Domain\Lead\Timeline;
use Psr\Clock\ClockInterface;
use Twig\Attribute\AsTwigFilter;

/** Libellés français du back-office, alignés sur le formulaire du site (apps/web/src/content/contact.ts). */
final readonly class AdminTwigExtension
{
    public function __construct(private ClockInterface $clock)
    {
    }

    #[AsTwigFilter('status_label')]
    public function statusLabel(LeadStatus $status): string
    {
        return match ($status) {
            LeadStatus::New => 'Nouvelle',
            LeadStatus::Qualified => 'Qualifiée',
            LeadStatus::Proposal => 'Proposition envoyée',
            LeadStatus::Won => 'Gagnée',
            LeadStatus::Lost => 'Perdue',
            LeadStatus::Archived => 'Archivée',
        };
    }

    #[AsTwigFilter('project_type_label')]
    public function projectTypeLabel(ProjectType $type): string
    {
        return match ($type) {
            ProjectType::Website => 'Site internet',
            ProjectType::MobileApp => 'Application mobile',
            ProjectType::WebApp => 'Application web',
            ProjectType::BusinessSoftware => 'Logiciel métier',
            ProjectType::ECommerce => 'E-commerce',
            ProjectType::Ai => 'IA & automatisation',
            ProjectType::Maintenance => 'Maintenance & hébergement',
            ProjectType::Other => 'Autre',
        };
    }

    /** Mêmes libellés que le site (apps/web/src/lib/portfolio.ts). */
    #[AsTwigFilter('category_label')]
    public function categoryLabel(ProjectCategory $category): string
    {
        return match ($category) {
            ProjectCategory::WebApp => 'App web',
            ProjectCategory::Mobile => 'Mobile',
            ProjectCategory::UxUi => 'Design UX/UI',
            ProjectCategory::ECommerce => 'E-commerce',
            ProjectCategory::Innovation => 'Innovation',
            ProjectCategory::Showcase => 'Site vitrine',
            ProjectCategory::Travel => 'Voyage',
            ProjectCategory::Ai => 'IA',
        };
    }

    #[AsTwigFilter('client_type_label')]
    public function clientTypeLabel(ClientType $type): string
    {
        return match ($type) {
            ClientType::Company => 'Entreprise',
            ClientType::Institution => 'Institution',
            ClientType::Ngo => 'ONG',
            ClientType::InHouse => 'Produit Genius At Work',
        };
    }

    #[AsTwigFilter('budget_label')]
    public function budgetLabel(?BudgetRange $budget): string
    {
        return match ($budget) {
            BudgetRange::LessThan10k => 'Moins de 10 k€',
            BudgetRange::From10kTo30k => '10 à 30 k€',
            BudgetRange::From30kTo80k => '30 à 80 k€',
            BudgetRange::MoreThan80k => 'Plus de 80 k€',
            BudgetRange::Undefined => 'À définir ensemble',
            null => 'Non précisé',
        };
    }

    #[AsTwigFilter('timeline_label')]
    public function timelineLabel(?Timeline $timeline): string
    {
        return match ($timeline) {
            Timeline::Asap => 'Dès que possible',
            Timeline::OneToThreeMonths => 'D’ici 1 à 3 mois',
            Timeline::ThreeToSixMonths => 'D’ici 3 à 6 mois',
            Timeline::Flexible => 'Pas d’urgence',
            null => 'Non précisée',
        };
    }

    #[AsTwigFilter('locale_label')]
    public function localeLabel(Locale $locale): string
    {
        return match ($locale) {
            Locale::French => 'Français',
            Locale::English => 'Anglais',
        };
    }

    /** « il y a 3 h », « hier », « il y a 12 j » ; au-delà d'un mois, la date. */
    #[AsTwigFilter('ago')]
    public function ago(\DateTimeImmutable $date): string
    {
        $seconds = $this->clock->now()->getTimestamp() - $date->getTimestamp();

        return match (true) {
            $seconds < 60 => 'à l’instant',
            $seconds < 3600 => \sprintf('il y a %d min', intdiv($seconds, 60)),
            $seconds < 86400 => \sprintf('il y a %d h', intdiv($seconds, 3600)),
            $seconds < 172800 => 'hier',
            $seconds < 30 * 86400 => \sprintf('il y a %d j', intdiv($seconds, 86400)),
            default => $date->setTimezone(new \DateTimeZone(AdminSettings::TIME_ZONE))->format('d/m/Y'),
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Content;

/** Les pages de services, dans l'ordre d'affichage : adresses fixes, seul leur contenu est modifiable. */
enum ServiceSlug: string
{
    case Websites = 'websites';
    case MobileApps = 'mobile-apps';
    case WebApps = 'web-apps';
    case BusinessSoftware = 'business-software';
    case Ai = 'ai';
    case ECommerce = 'e-commerce';
    case MaintenanceHosting = 'maintenance-hosting';
}

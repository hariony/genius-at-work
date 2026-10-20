<?php

declare(strict_types=1);

namespace App\Domain\Lead;

/** Aligné sur PROJECT_TYPES de packages/types/src/contact.ts. */
enum ProjectType: string
{
    case Website = 'website';
    case MobileApp = 'mobile-app';
    case WebApp = 'web-app';
    case BusinessSoftware = 'business-software';
    case ECommerce = 'e-commerce';
    case Ai = 'ai';
    case Maintenance = 'maintenance';
    case Other = 'other';
}

<?php

declare(strict_types=1);

namespace App\Domain\Content;

/** Taxonomie du portfolio (libellés : apps/web/src/lib/portfolio.ts et back-office). */
enum ProjectCategory: string
{
    case WebApp = 'web-app';
    case Mobile = 'mobile';
    case UxUi = 'ux-ui';
    case ECommerce = 'e-commerce';
    case Innovation = 'innovation';
    case Showcase = 'showcase';
    case Travel = 'travel';
    case Ai = 'ai';
}

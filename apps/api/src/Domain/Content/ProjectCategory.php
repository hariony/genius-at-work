<?php

declare(strict_types=1);

namespace App\Domain\Content;

/** Taxonomie du portfolio (libellés : Pages et textes › Réalisations › Catégories de projet). */
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

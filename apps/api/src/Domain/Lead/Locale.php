<?php

declare(strict_types=1);

namespace App\Domain\Lead;

enum Locale: string
{
    case French = 'fr';
    case English = 'en';
}

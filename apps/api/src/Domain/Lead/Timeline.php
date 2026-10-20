<?php

declare(strict_types=1);

namespace App\Domain\Lead;

/** Aligné sur TIMELINES de packages/types/src/contact.ts. */
enum Timeline: string
{
    case Asap = 'asap';
    case OneToThreeMonths = '1-3-months';
    case ThreeToSixMonths = '3-6-months';
    case Flexible = 'flexible';
}

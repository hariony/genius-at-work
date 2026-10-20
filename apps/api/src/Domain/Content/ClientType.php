<?php

declare(strict_types=1);

namespace App\Domain\Content;

enum ClientType: string
{
    case Company = 'company';
    case Institution = 'institution';
    case Ngo = 'ngo';
    case InHouse = 'in-house';
}

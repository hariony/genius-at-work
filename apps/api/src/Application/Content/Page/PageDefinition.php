<?php

declare(strict_types=1);

namespace App\Application\Content\Page;

/** Une page (ou un bloc de textes partagé) éditable dans le back-office. */
final readonly class PageDefinition
{
    /** @param list<Field> $fields */
    public function __construct(
        public string $id,
        public string $title,
        public string $group,
        public string $description,
        public ?string $sitePath,
        public array $fields,
    ) {
    }
}

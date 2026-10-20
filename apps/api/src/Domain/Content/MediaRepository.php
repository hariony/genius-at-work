<?php

declare(strict_types=1);

namespace App\Domain\Content;

interface MediaRepository
{
    public function get(string $id): ?Media;

    public function save(Media $media): void;
}

<?php

declare(strict_types=1);

namespace App\Domain\Content;

/** Image envoyée depuis le back-office, servie par l'API sous /media/{fileName}. */
class Media
{
    public const array MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    public function __construct(
        private string $id,
        private string $fileName,
        private string $mimeType,
        private int $width,
        private int $height,
        private int $byteSize,
        private string $originalName,
        private \DateTimeImmutable $uploadedAt,
    ) {
        if (!isset(self::MIME_TYPES[$mimeType])) {
            throw new \InvalidArgumentException(\sprintf('Format d’image non accepté : %s.', $mimeType));
        }
        if ($width < 1 || $height < 1) {
            throw new \InvalidArgumentException('Dimensions d’image invalides.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function fileName(): string
    {
        return $this->fileName;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function width(): int
    {
        return $this->width;
    }

    public function height(): int
    {
        return $this->height;
    }

    public function byteSize(): int
    {
        return $this->byteSize;
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function uploadedAt(): \DateTimeImmutable
    {
        return $this->uploadedAt;
    }

    public function path(): string
    {
        return '/media/'.$this->fileName;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Domain\Content\Media;
use App\Domain\Content\MediaRepository;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Uid\Ulid;

/**
 * Enregistre les images envoyées depuis le back-office. Le type réel est lu dans le fichier
 * (jamais l'extension ni l'en-tête du navigateur) ; le nom stocké est aléatoire.
 * Le site les télécharge et les optimise au build : l'original est conservé tel quel.
 */
final readonly class MediaLibrary
{
    public const int MAX_BYTES = 10 * 1024 * 1024;
    public const int MAX_SIDE = 8000;

    public function __construct(
        private MediaRepository $media,
        private ClockInterface $clock,
        private LoggerInterface $logger,
        #[Autowire('%app.media_dir%')]
        private string $directory,
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function upload(string $path, string $originalName, string $actor): Media
    {
        $media = $this->store($path, (string) new Ulid(), null, $originalName);
        $this->logger->info('Image ajoutée.', ['media' => $media->fileName(), 'actor' => $actor]);

        return $media;
    }

    /** Import initial : identifiant et nom de fichier stables (data/media/{fichier}). */
    public function importFile(string $path): Media
    {
        $name = pathinfo($path, \PATHINFO_FILENAME);

        return $this->media->get('seed-'.$name)
            ?? $this->store($path, 'seed-'.$name, $name, basename($path));
    }

    private function store(string $path, string $id, ?string $baseName, string $originalName): Media
    {
        $size = @filesize($path);
        if (false === $size || 0 === $size) {
            throw new MediaRejected('Fichier vide ou illisible.');
        }
        if ($size > self::MAX_BYTES) {
            throw new MediaRejected(\sprintf('Image trop lourde (%.1f Mo) : 10 Mo maximum.', $size / 1048576));
        }
        $info = @getimagesize($path);
        $mime = false === $info ? null : $info['mime'];
        $extension = Media::MIME_TYPES[$mime] ?? null;
        if (false === $info || null === $extension) {
            throw new MediaRejected('Format non accepté : JPEG, PNG, WebP ou AVIF.');
        }
        [$width, $height] = $info;
        if ($width > self::MAX_SIDE || $height > self::MAX_SIDE) {
            throw new MediaRejected(\sprintf('Image trop grande (%d × %d px) : 8000 px maximum.', $width, $height));
        }

        $fileName = ($baseName ?? strtolower($id)).'.'.$extension;
        $this->filesystem->copy($path, $this->directory.'/'.$fileName, true);
        // Droits demandés par l'hébergeur, quel que soit le umask de PHP.
        $this->filesystem->chmod($this->directory.'/'.$fileName, 0o644);

        $media = new Media($id, $fileName, (string) $mime, $width, $height, $size, mb_substr($originalName, 0, 255), $this->clock->now());
        $this->media->save($media);

        return $media;
    }
}

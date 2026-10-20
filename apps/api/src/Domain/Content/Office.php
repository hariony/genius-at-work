<?php

declare(strict_types=1);

namespace App\Domain\Content;

final readonly class Office
{
    /**
     * @param list<string> $addressLines
     * @param list<Phone>  $phones
     */
    public function __construct(
        public string $id,
        public LocalizedText $name,
        public string $city,
        public array $addressLines,
        public array $phones,
        public string $timeZone,
        public float $latitude,
        public float $longitude,
    ) {
        if (!preg_match('/^[a-z0-9-]{2,40}$/', $id)) {
            throw new \InvalidArgumentException('Identifiant de bureau invalide.');
        }
        if ('' === trim($city) || [] === $addressLines) {
            throw new \InvalidArgumentException('Ville et adresse obligatoires.');
        }
        if (!\in_array($timeZone, \DateTimeZone::listIdentifiers(), true)) {
            throw new \InvalidArgumentException(\sprintf('Fuseau horaire inconnu : %s.', $timeZone));
        }
        if (abs($latitude) > 90 || abs($longitude) > 180) {
            throw new \InvalidArgumentException('Coordonnées hors limites.');
        }
    }

    /** Décalage horaire à la date donnée : « UTC+3 », « UTC+5:30 », « UTC−1 ». */
    public function utcLabel(\DateTimeImmutable $at): string
    {
        $offset = (new \DateTimeZone($this->timeZone))->getOffset($at);
        if (0 === $offset) {
            return 'UTC';
        }
        $minutes = intdiv(abs($offset), 60);
        $label = (string) intdiv($minutes, 60);
        if (0 !== $minutes % 60) {
            $label .= \sprintf(':%02d', $minutes % 60);
        }

        return 'UTC'.($offset > 0 ? '+' : '−').$label;
    }
}

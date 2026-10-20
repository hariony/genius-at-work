<?php

declare(strict_types=1);

namespace App\UserInterface\Admin\Content;

use App\Domain\Content\LocalizedText;
use App\Domain\Content\Office;
use App\Domain\Content\Phone;
use App\Domain\Content\SiteSettings;
use App\Domain\Content\SocialLink;

/**
 * Formulaire « Réglages » : valeurs saisies (rendues telles quelles en cas d'erreur),
 * messages par champ, puis conversion en objet du domaine.
 *
 * @phpstan-type OfficeRow array{id: string, name_fr: string, name_en: string, city: string, address: string, phones: string, timeZone: string, lat: string, lng: string}
 * @phpstan-type SocialRow array{label: string, url: string}
 */
final class SettingsForm
{
    /** Lignes vides proposées pour ajouter un réseau social. */
    private const int EMPTY_SOCIAL_ROWS = 2;

    /** @var array<string, string> chemin du champ → message */
    public array $errors = [];

    /**
     * @param list<SocialRow> $socials
     * @param list<OfficeRow> $offices
     */
    private function __construct(
        public string $name,
        public string $email,
        public array $socials,
        public array $offices,
    ) {
    }

    public static function fromSettings(?SiteSettings $settings): self
    {
        if (null === $settings) {
            return new self('', '', [], [self::emptyOffice()]);
        }

        return new self(
            $settings->name,
            $settings->email,
            array_map(static fn (SocialLink $s): array => ['label' => $s->label, 'url' => $s->url], $settings->socials),
            array_map(static fn (Office $o): array => [
                'id' => $o->id,
                'name_fr' => $o->name->fr,
                'name_en' => $o->name->en,
                'city' => $o->city,
                'address' => implode("\n", $o->addressLines),
                'phones' => implode("\n", array_map(static fn (Phone $p): string => $p->display, $o->phones)),
                'timeZone' => $o->timeZone,
                'lat' => (string) $o->latitude,
                'lng' => (string) $o->longitude,
            ], $settings->offices),
        );
    }

    /** @param array<mixed> $input champ « settings » de la requête */
    public static function fromInput(array $input): self
    {
        $socials = [];
        foreach (self::rows($input, 'socials') as $row) {
            $social = ['label' => self::string($row, 'label'), 'url' => self::string($row, 'url')];
            if ('' !== $social['label'] || '' !== $social['url']) {
                $socials[] = $social;
            }
        }
        $offices = [];
        foreach (self::rows($input, 'offices') as $row) {
            $offices[] = [
                'id' => self::string($row, 'id'),
                'name_fr' => self::string($row, 'name_fr'),
                'name_en' => self::string($row, 'name_en'),
                'city' => self::string($row, 'city'),
                'address' => self::string($row, 'address'),
                'phones' => self::string($row, 'phones'),
                'timeZone' => self::string($row, 'timeZone'),
                'lat' => str_replace(',', '.', self::string($row, 'lat')),
                'lng' => str_replace(',', '.', self::string($row, 'lng')),
            ];
        }

        return new self(self::string($input, 'name'), self::string($input, 'email'), $socials, $offices);
    }

    public function addOffice(): void
    {
        $this->offices[] = self::emptyOffice();
    }

    public function removeOffice(int $index): void
    {
        $offices = $this->offices;
        unset($offices[$index]);
        $this->offices = array_values($offices);
    }

    /**
     * Lignes affichées : réseaux saisis + quelques lignes vides pour en ajouter.
     *
     * @return list<SocialRow>
     */
    public function socialRows(): array
    {
        return [...$this->socials, ...array_fill(0, self::EMPTY_SOCIAL_ROWS, ['label' => '', 'url' => ''])];
    }

    public function validate(): bool
    {
        $this->errors = [];
        if ('' === $this->name) {
            $this->errors['name'] = 'Indiquez le nom de l’entreprise.';
        }
        if (false === filter_var($this->email, \FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Adresse e-mail invalide.';
        }
        foreach ($this->socials as $i => $social) {
            if ('' === $social['label']) {
                $this->errors["socials.$i.label"] = 'Nom du réseau manquant.';
            }
            if (!str_starts_with($social['url'], 'https://') || false === filter_var($social['url'], \FILTER_VALIDATE_URL)) {
                $this->errors["socials.$i.url"] = 'Adresse complète en https:// attendue.';
            }
        }
        if ([] === $this->offices) {
            $this->errors['offices'] = 'Le site a besoin d’au moins un bureau.';
        }
        $ids = [];
        foreach ($this->offices as $i => $office) {
            $this->validateOffice($i, $office);
            $id = $this->officeId($office);
            if (isset($ids[$id])) {
                $this->errors["offices.$i.city"] = 'Deux bureaux dans la même ville : précisez la ville.';
            }
            $ids[$id] = true;
        }

        return [] === $this->errors;
    }

    public function toSettings(): SiteSettings
    {
        return new SiteSettings(
            name: $this->name,
            email: $this->email,
            socials: array_map(static fn (array $s): SocialLink => new SocialLink($s['label'], $s['url']), $this->socials),
            offices: array_map(fn (array $o): Office => new Office(
                id: $this->officeId($o),
                name: new LocalizedText($o['name_fr'], $o['name_en']),
                city: $o['city'],
                addressLines: self::lines($o['address']),
                phones: array_map(static fn (string $p): Phone => new Phone($p), self::lines($o['phones'])),
                timeZone: $o['timeZone'],
                latitude: (float) $o['lat'],
                longitude: (float) $o['lng'],
            ), $this->offices),
        );
    }

    /** @param OfficeRow $office */
    private function validateOffice(int $i, array $office): void
    {
        $required = ['name_fr' => 'Nom en français manquant.', 'name_en' => 'Nom en anglais manquant.', 'city' => 'Ville manquante.'];
        foreach ($required as $field => $message) {
            if ('' === $office[$field]) {
                $this->errors["offices.$i.$field"] = $message;
            }
        }
        if ([] === self::lines($office['address'])) {
            $this->errors["offices.$i.address"] = 'Adresse manquante.';
        }
        foreach (self::lines($office['phones']) as $phone) {
            if (!preg_match(Phone::PATTERN, $phone)) {
                $this->errors["offices.$i.phones"] = \sprintf('« %s » : format international attendu, par exemple +261 34 00 000 00.', $phone);
            }
        }
        if (!\in_array($office['timeZone'], \DateTimeZone::listIdentifiers(), true)) {
            $this->errors["offices.$i.timeZone"] = 'Choisissez un fuseau horaire.';
        }
        if (!is_numeric($office['lat']) || abs((float) $office['lat']) > 90) {
            $this->errors["offices.$i.lat"] = 'Latitude entre -90 et 90.';
        }
        if (!is_numeric($office['lng']) || abs((float) $office['lng']) > 180) {
            $this->errors["offices.$i.lng"] = 'Longitude entre -180 et 180.';
        }
    }

    /**
     * Identifiant conservé ; pour un nouveau bureau, dérivé de la ville.
     *
     * @param OfficeRow $office
     */
    private function officeId(array $office): string
    {
        if ('' !== $office['id']) {
            return $office['id'];
        }
        $slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', (string) iconv('UTF-8', 'ASCII//TRANSLIT', $office['city'])), '-'));

        return '' === $slug ? 'bureau' : substr($slug, 0, 40);
    }

    /** @return OfficeRow */
    private static function emptyOffice(): array
    {
        return ['id' => '', 'name_fr' => '', 'name_en' => '', 'city' => '', 'address' => '', 'phones' => '', 'timeZone' => '', 'lat' => '', 'lng' => ''];
    }

    /** @return list<string> lignes non vides, sans espaces superflus */
    private static function lines(string $text): array
    {
        return array_values(array_filter(array_map(trim(...), explode("\n", $text)), static fn (string $l): bool => '' !== $l));
    }

    /**
     * @param array<mixed> $input
     *
     * @return list<array<mixed>>
     */
    private static function rows(array $input, string $key): array
    {
        $rows = $input[$key] ?? [];

        return \is_array($rows) ? array_values(array_filter($rows, is_array(...))) : [];
    }

    /** @param array<mixed> $input */
    private static function string(array $input, string $key): string
    {
        $value = $input[$key] ?? '';

        return \is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : '';
    }
}

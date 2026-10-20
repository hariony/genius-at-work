<?php

declare(strict_types=1);

namespace App\Application\Content;

use App\Domain\Content\LocalizedText;
use App\Domain\Content\Office;
use App\Domain\Content\Phone;
use App\Domain\Content\SiteSettings;
use App\Domain\Content\SocialLink;

/**
 * Réglages ⇄ tableau : format de stockage, d'import (apps/api/data/content-seed.json)
 * et, avec les champs calculés, de la route lue par le site.
 *
 * @phpstan-type OfficeData array{id: string, name: array{fr: string, en: string}, city: string, addressLines: list<string>, phones: list<string>, timeZone: string, coordinates: array{lat: float|int, lng: float|int}}
 * @phpstan-type SettingsData array{name: string, email: string, socials: list<array{label: string, url: string}>, offices: list<OfficeData>}
 */
final class SettingsMapper
{
    /** @param SettingsData $data */
    public static function fromArray(array $data): SiteSettings
    {
        return new SiteSettings(
            name: $data['name'],
            email: $data['email'],
            socials: array_map(
                static fn (array $social): SocialLink => new SocialLink($social['label'], $social['url']),
                $data['socials'],
            ),
            offices: array_map(
                static fn (array $office): Office => new Office(
                    id: $office['id'],
                    name: LocalizedText::fromArray($office['name']),
                    city: $office['city'],
                    addressLines: $office['addressLines'],
                    phones: array_map(static fn (string $phone): Phone => new Phone($phone), $office['phones']),
                    timeZone: $office['timeZone'],
                    latitude: (float) $office['coordinates']['lat'],
                    longitude: (float) $office['coordinates']['lng'],
                ),
                $data['offices'],
            ),
        );
    }

    /** @return SettingsData */
    public static function toArray(SiteSettings $settings): array
    {
        return [
            'name' => $settings->name,
            'email' => $settings->email,
            'socials' => array_map(
                static fn (SocialLink $social): array => ['label' => $social->label, 'url' => $social->url],
                $settings->socials,
            ),
            'offices' => array_map(
                static fn (Office $office): array => [
                    'id' => $office->id,
                    'name' => $office->name->toArray(),
                    'city' => $office->city,
                    'addressLines' => $office->addressLines,
                    'phones' => array_map(static fn (Phone $phone): string => $phone->display, $office->phones),
                    'timeZone' => $office->timeZone,
                    'coordinates' => ['lat' => $office->latitude, 'lng' => $office->longitude],
                ],
                $settings->offices,
            ),
        ];
    }

    /**
     * Format attendu par le site (apps/web/src/lib/content-api) : identifiants, liens tel: et
     * décalage horaire calculés ici, une seule fois.
     *
     * @return array<string, mixed>
     */
    public static function toSite(SiteSettings $settings, \DateTimeImmutable $now): array
    {
        return [
            'name' => $settings->name,
            'email' => $settings->email,
            'socials' => array_map(
                static fn (SocialLink $social): array => ['id' => $social->id(), 'label' => $social->label, 'href' => $social->url],
                $settings->socials,
            ),
            'offices' => array_map(
                static fn (Office $office): array => [
                    'id' => $office->id,
                    'name' => $office->name->toArray(),
                    'city' => $office->city,
                    'addressLines' => $office->addressLines,
                    'phones' => array_map(
                        static fn (Phone $phone): array => ['display' => $phone->display, 'e164' => $phone->e164],
                        $office->phones,
                    ),
                    'timeZone' => $office->timeZone,
                    'utcLabel' => $office->utcLabel($now),
                    'coordinates' => ['lat' => $office->latitude, 'lng' => $office->longitude],
                ],
                $settings->offices,
            ),
        ];
    }
}

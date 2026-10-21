<?php

declare(strict_types=1);

namespace App\UserInterface\Admin\Content;

use App\Domain\Content\ClientType;
use App\Domain\Content\LocalizedText;
use App\Domain\Content\Media;
use App\Domain\Content\Project;
use App\Domain\Content\ProjectCategory;
use App\Domain\Content\ProjectDetails;

/**
 * Formulaire « Projet » : valeurs saisies (rendues telles quelles en cas d'erreur), messages par
 * champ, puis conversion en objet du domaine. L'image est envoyée à part (MediaLibrary) ; le
 * formulaire ne garde que son identifiant, pour ne pas la redemander si un autre champ est faux.
 */
final class ProjectForm
{
    /** @var array<string, string> chemin du champ → message */
    public array $errors = [];

    /** @param list<string> $categories */
    private function __construct(
        public string $slug,
        public string $title,
        public string $client,
        public string $clientType,
        public string $periodStart,
        public string $periodEnd,
        public array $categories,
        public string $technologies,
        public string $url,
        public string $summaryFr,
        public string $summaryEn,
        public string $coverId,
        public string $coverAltFr,
        public string $coverAltEn,
        public bool $featured,
        public bool $published,
        public string $tint,
    ) {
    }

    public static function empty(): self
    {
        return new self('', '', '', '', '', '', [], '', '', '', '', '', '', '', false, false, '');
    }

    public static function fromProject(Project $project): self
    {
        $d = $project->details();

        return new self(
            $d->slug,
            $d->title,
            $d->client ?? '',
            $d->clientType->value ?? '',
            (string) $d->periodStart,
            (string) $d->periodEnd,
            array_map(static fn (ProjectCategory $c): string => $c->value, $d->categories),
            implode(', ', $d->technologies),
            $d->url ?? '',
            $d->summary->fr,
            $d->summary->en,
            $d->cover->id(),
            $d->coverAlt->fr,
            $d->coverAlt->en,
            $d->featured,
            $d->published,
            $d->tint ?? '',
        );
    }

    /** @param array<mixed> $input champ « project » de la requête */
    public static function fromInput(array $input): self
    {
        $categories = $input['categories'] ?? [];

        return new self(
            strtolower(self::string($input, 'slug')),
            self::string($input, 'title'),
            self::string($input, 'client'),
            self::string($input, 'clientType'),
            self::string($input, 'periodStart'),
            self::string($input, 'periodEnd'),
            \is_array($categories) ? array_values(array_filter($categories, is_string(...))) : [],
            self::string($input, 'technologies'),
            self::string($input, 'url'),
            self::string($input, 'summary_fr'),
            self::string($input, 'summary_en'),
            self::string($input, 'coverId'),
            self::string($input, 'coverAlt_fr'),
            self::string($input, 'coverAlt_en'),
            '1' === ($input['featured'] ?? null),
            '1' === ($input['published'] ?? null),
            self::tint(self::string($input, 'tint')),
        );
    }

    /** Pour un nouveau projet sans adresse saisie : dérivée du titre. */
    public function suggestSlug(): void
    {
        if ('' === $this->slug && '' !== $this->title) {
            $this->slug = substr(trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT', $this->title))), '-'), 0, 80);
        }
    }

    public function validate(?Media $cover): bool
    {
        $this->errors = [];
        if ('' === $this->title) {
            $this->errors['title'] = 'Indiquez le nom du projet.';
        }
        // Adresse vide et nom vide : seul le nom est signalé (l'adresse en sera déduite).
        if (('' !== $this->slug || '' !== $this->title) && (!preg_match(ProjectDetails::SLUG_PATTERN, $this->slug) || \strlen($this->slug) > 80)) {
            $this->errors['slug'] = 'Minuscules, chiffres et tirets uniquement (ex. mon-projet).';
        }
        if ('' !== $this->clientType && null === ClientType::tryFrom($this->clientType)) {
            $this->errors['clientType'] = 'Type de client inconnu.';
        }
        $this->validatePeriod();
        if ([] === $this->categories) {
            $this->errors['categories'] = 'Choisissez au moins une catégorie.';
        }
        foreach ($this->categories as $category) {
            if (null === ProjectCategory::tryFrom($category)) {
                $this->errors['categories'] = 'Catégorie inconnue.';
            }
        }
        if ('' !== $this->url && (!preg_match('#^https?://#', $this->url) || false === filter_var($this->url, \FILTER_VALIDATE_URL))) {
            $this->errors['url'] = 'Adresse complète attendue, par exemple https://exemple.com/.';
        }
        foreach (['summaryFr' => 'Résumé en français manquant.', 'summaryEn' => 'Résumé en anglais manquant.', 'coverAltFr' => 'Description en français manquante.', 'coverAltEn' => 'Description en anglais manquante.'] as $field => $message) {
            if ('' === $this->{$field}) {
                $this->errors[$field] = $message;
            }
        }
        if ('' !== $this->tint && !preg_match(ProjectDetails::TINT_PATTERN, $this->tint)) {
            $this->errors['tint'] = 'Couleur au format #rrggbb, par exemple #1d34d6 (ou laissez vide).';
        }
        if (null === $cover) {
            $this->errors['cover'] ??= 'Ajoutez une capture du projet.';
        }

        return [] === $this->errors;
    }

    public function toDetails(Media $cover): ProjectDetails
    {
        return new ProjectDetails(
            slug: $this->slug,
            title: $this->title,
            client: '' === $this->client ? null : $this->client,
            clientType: ClientType::tryFrom($this->clientType),
            periodStart: '' === $this->periodStart ? null : (int) $this->periodStart,
            periodEnd: '' === $this->periodEnd ? null : (int) $this->periodEnd,
            categories: array_map(static fn (string $c): ProjectCategory => ProjectCategory::from($c), $this->categories),
            technologies: $this->technologyList(),
            url: '' === $this->url ? null : $this->url,
            summary: new LocalizedText($this->summaryFr, $this->summaryEn),
            cover: $cover,
            coverAlt: new LocalizedText($this->coverAltFr, $this->coverAltEn),
            featured: $this->featured,
            published: $this->published,
            tint: '' === $this->tint ? null : $this->tint,
        );
    }

    /** @return list<string> technologies sans doublon, dans l'ordre saisi */
    private function technologyList(): array
    {
        $names = array_filter(array_map(trim(...), preg_split('/[,\n]/', $this->technologies) ?: []), static fn (string $t): bool => '' !== $t);

        return array_values(array_unique($names));
    }

    private function validatePeriod(): void
    {
        if ('' === $this->periodStart && '' === $this->periodEnd) {
            return;
        }
        foreach (['periodStart', 'periodEnd'] as $field) {
            if (!preg_match('/^\d{4}$/', $this->{$field}) || (int) $this->{$field} < 1990 || (int) $this->{$field} > 2100) {
                $this->errors[$field] = 'Année sur 4 chiffres (ou laissez les deux champs vides).';
            }
        }
        if (!isset($this->errors['periodStart']) && !isset($this->errors['periodEnd']) && (int) $this->periodStart > (int) $this->periodEnd) {
            $this->errors['periodEnd'] = 'L’année de fin précède celle de début.';
        }
    }

    /** « 1D34D6 » ou « #1D34D6 » → « #1d34d6 » ; le reste est gardé tel quel pour être signalé. */
    private static function tint(string $value): string
    {
        $value = strtolower($value);

        return preg_match('/^[0-9a-f]{6}$/', $value) ? '#'.$value : $value;
    }

    /** @param array<mixed> $input */
    private static function string(array $input, string $key): string
    {
        $value = $input[$key] ?? '';

        return \is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : '';
    }
}

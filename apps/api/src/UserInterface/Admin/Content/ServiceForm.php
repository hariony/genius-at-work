<?php

declare(strict_types=1);

namespace App\UserInterface\Admin\Content;

use App\Domain\Content\LocalizedText;
use App\Domain\Content\ServiceDetails;

/** Formulaire « Service » : textes FR/EN, périmètre (une ligne par point), technologies, projets liés. */
final class ServiceForm
{
    private const array TEXTS = ['title', 'summary', 'intro', 'scope'];

    /** @var array<string, string> chemin du champ → message */
    public array $errors = [];

    /**
     * @param array<string, string> $texts           « title_fr », « scope_en »…
     * @param list<string>          $relatedProjects
     */
    private function __construct(
        public array $texts,
        public string $technologies,
        public array $relatedProjects,
    ) {
    }

    public static function fromDetails(ServiceDetails $d): self
    {
        return new self([
            'title_fr' => $d->title->fr,
            'title_en' => $d->title->en,
            'summary_fr' => $d->summary->fr,
            'summary_en' => $d->summary->en,
            'intro_fr' => $d->intro->fr,
            'intro_en' => $d->intro->en,
            'scope_fr' => implode("\n", $d->scopeFr),
            'scope_en' => implode("\n", $d->scopeEn),
        ], implode(', ', $d->technologies), $d->relatedProjects);
    }

    /** @param array<mixed> $input champ « service » de la requête */
    public static function fromInput(array $input): self
    {
        $texts = [];
        foreach (self::TEXTS as $name) {
            foreach (['fr', 'en'] as $lang) {
                $value = $input[$name.'_'.$lang] ?? '';
                $texts[$name.'_'.$lang] = \is_string($value) ? trim(str_replace("\r\n", "\n", $value)) : '';
            }
        }
        $technologies = $input['technologies'] ?? '';
        $related = $input['relatedProjects'] ?? [];

        return new self(
            $texts,
            \is_string($technologies) ? trim($technologies) : '',
            \is_array($related) ? array_values(array_filter($related, is_string(...))) : [],
        );
    }

    public function validate(): bool
    {
        $this->errors = [];
        foreach ($this->texts as $field => $value) {
            if ('' === $value) {
                $this->errors[$field] = str_ends_with($field, '_fr') ? 'Texte en français manquant.' : 'Texte en anglais manquant.';
            }
        }

        return [] === $this->errors;
    }

    public function toDetails(): ServiceDetails
    {
        return new ServiceDetails(
            title: new LocalizedText($this->texts['title_fr'], $this->texts['title_en']),
            summary: new LocalizedText($this->texts['summary_fr'], $this->texts['summary_en']),
            intro: new LocalizedText($this->texts['intro_fr'], $this->texts['intro_en']),
            scopeFr: self::lines($this->texts['scope_fr']),
            scopeEn: self::lines($this->texts['scope_en']),
            technologies: array_values(array_unique(array_filter(array_map(trim(...), explode(',', $this->technologies)), static fn (string $t): bool => '' !== $t))),
            relatedProjects: $this->relatedProjects,
        );
    }

    /** @return list<string> */
    private static function lines(string $text): array
    {
        return array_values(array_filter(array_map(trim(...), explode("\n", $text)), static fn (string $l): bool => '' !== $l));
    }
}

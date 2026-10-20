<?php

declare(strict_types=1);

namespace App\Application\Content\Page;

/**
 * Un champ d'une page éditable. Par défaut : texte obligatoire, saisi en français et en anglais.
 * « shared » : une seule valeur pour les deux langues (lien, nombre, nom de technologie…).
 * Une section (clé null) ne sert qu'à regrouper visuellement : ses champs restent au même niveau.
 */
final readonly class Field
{
    public const string LINE = 'line';
    public const string TEXT = 'text';
    public const string LINES = 'lines';
    public const string NUMBER = 'number';
    public const string CHOICE = 'choice';
    public const string GROUP = 'group';
    public const string LIST = 'list';

    /**
     * @param list<Field>           $fields  champs d'un groupe ou d'un élément de liste
     * @param array<string, string> $choices valeur → libellé
     */
    private function __construct(
        public string $type,
        public ?string $key,
        public string $label,
        public string $hint = '',
        public bool $shared = false,
        public bool $optional = false,
        public array $fields = [],
        public array $choices = [],
        public int $min = 0,
        public ?int $max = null,
        public int $rows = 3,
    ) {
    }

    public static function line(string $key, string $label): self
    {
        return new self(self::LINE, $key, $label);
    }

    public static function text(string $key, string $label, int $rows = 3): self
    {
        return new self(self::TEXT, $key, $label, rows: $rows);
    }

    /** Liste de textes courts, saisis un par ligne. */
    public static function lines(string $key, string $label, int $rows = 4): self
    {
        return new self(self::LINES, $key, $label, rows: $rows);
    }

    public static function number(string $key, string $label): self
    {
        return new self(self::NUMBER, $key, $label, shared: true);
    }

    /** @param array<string, string> $choices */
    public static function choice(string $key, string $label, array $choices): self
    {
        return new self(self::CHOICE, $key, $label, shared: true, choices: $choices);
    }

    /** @param list<Field> $fields */
    public static function group(string $key, string $label, array $fields): self
    {
        return new self(self::GROUP, $key, $label, fields: $fields);
    }

    /** @param list<Field> $fields */
    public static function section(string $label, array $fields): self
    {
        return new self(self::GROUP, null, $label, fields: $fields);
    }

    /**
     * Liste d'éléments : une ligne vide est proposée pour en ajouter, un élément vidé est retiré.
     *
     * @param list<Field> $fields
     */
    public static function list(string $key, string $label, array $fields, int $min = 1, ?int $max = null): self
    {
        return new self(self::LIST, $key, $label, fields: $fields, min: $min, max: $max);
    }

    public function hint(string $hint): self
    {
        return $this->copy(hint: $hint);
    }

    public function shared(): self
    {
        return $this->copy(shared: true);
    }

    public function optional(): self
    {
        return $this->copy(optional: true);
    }

    /** Liste de longueur fixe (ni ajout ni retrait). */
    public function fixed(int $count): self
    {
        return $this->copy(min: $count, max: $count);
    }

    public function isLeaf(): bool
    {
        return self::GROUP !== $this->type && self::LIST !== $this->type;
    }

    private function copy(?string $hint = null, ?bool $shared = null, ?bool $optional = null, ?int $min = null, ?int $max = null): self
    {
        return new self(
            $this->type,
            $this->key,
            $this->label,
            $hint ?? $this->hint,
            $shared ?? $this->shared,
            $optional ?? $this->optional,
            $this->fields,
            $this->choices,
            $min ?? $this->min,
            $max ?? $this->max,
            $this->rows,
        );
    }
}

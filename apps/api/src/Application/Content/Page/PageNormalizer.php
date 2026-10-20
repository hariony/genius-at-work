<?php

declare(strict_types=1);

namespace App\Application\Content\Page;

/**
 * Met les valeurs saisies (ou importées) dans la forme décrite par la page : textes nettoyés,
 * listes sans lignes vides, nombres convertis, champs inconnus ignorés. Signale chaque champ
 * manquant ou invalide, par langue.
 *
 * Entrée : un tableau par langue, plus `shared` pour les valeurs communes aux deux langues.
 */
final class PageNormalizer
{
    /**
     * @param array<mixed> $fr
     * @param array<mixed> $en
     * @param array<mixed> $shared
     */
    public static function normalize(PageDefinition $page, array $fr, array $en, array $shared): PageData
    {
        $outFr = [];
        $outEn = [];
        $errors = [];
        self::walk($page->fields, $fr, $en, $shared, [], $outFr, $outEn, $errors);

        return new PageData($outFr, $outEn, $errors);
    }

    /**
     * Valeurs déjà enregistrées (ou importées) : les communes sont lues dans la version française.
     *
     * @param array<mixed> $fr
     * @param array<mixed> $en
     */
    public static function fromStored(PageDefinition $page, array $fr, array $en): PageData
    {
        return self::normalize($page, $fr, $en, $fr);
    }

    /**
     * @param list<Field>           $fields
     * @param array<mixed>          $fr
     * @param array<mixed>          $en
     * @param array<mixed>          $shared
     * @param list<string>          $path
     * @param array<string, mixed>  $outFr
     * @param array<string, mixed>  $outEn
     * @param array<string, string> $errors
     *
     * @param-out array<string, mixed> $outFr
     * @param-out array<string, mixed> $outEn
     * @param-out array<string, string> $errors
     */
    private static function walk(array $fields, array $fr, array $en, array $shared, array $path, array &$outFr, array &$outEn, array &$errors): void
    {
        foreach ($fields as $field) {
            if (null === $field->key) {
                self::walk($field->fields, $fr, $en, $shared, $path, $outFr, $outEn, $errors);
                continue;
            }
            $key = $field->key;
            $here = [...$path, $key];

            if (Field::GROUP === $field->type) {
                $subFr = [];
                $subEn = [];
                self::walk($field->fields, self::arr($fr, $key), self::arr($en, $key), self::arr($shared, $key), $here, $subFr, $subEn, $errors);
                $outFr[$key] = $subFr;
                $outEn[$key] = $subEn;
                continue;
            }

            if (Field::LIST === $field->type) {
                [$outFr[$key], $outEn[$key]] = self::rows($field, self::arr($fr, $key), self::arr($en, $key), self::arr($shared, $key), $here, $errors);
                continue;
            }

            if ($field->shared) {
                $value = self::value($field, $shared[$key] ?? null);
                self::check($field, $value, '_', $here, $errors);
                $outFr[$key] = $value;
                $outEn[$key] = $value;
                continue;
            }

            $outFr[$key] = self::value($field, $fr[$key] ?? null);
            $outEn[$key] = self::value($field, $en[$key] ?? null);
            self::check($field, $outFr[$key], 'fr', $here, $errors);
            self::check($field, $outEn[$key], 'en', $here, $errors);
        }
    }

    /**
     * @param array<mixed>          $fr
     * @param array<mixed>          $en
     * @param array<mixed>          $shared
     * @param list<string>          $path
     * @param array<string, string> $errors
     *
     * @param-out array<string, string> $errors
     *
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private static function rows(Field $field, array $fr, array $en, array $shared, array $path, array &$errors): array
    {
        $indexes = array_unique([...array_keys($fr), ...array_keys($en), ...array_keys($shared)]);
        sort($indexes);
        $fixed = null !== $field->max && $field->min === $field->max;
        $listFr = [];
        $listEn = [];
        foreach ($indexes as $index) {
            $rowFr = [];
            $rowEn = [];
            $rowErrors = [];
            $rowPath = [...$path, (string) \count($listFr)];
            self::walk($field->fields, self::arr($fr, $index), self::arr($en, $index), self::arr($shared, $index), $rowPath, $rowFr, $rowEn, $rowErrors);
            // Ligne entièrement vide : ajout non utilisé, ou élément retiré.
            if (!$fixed && self::isEmpty($rowFr) && self::isEmpty($rowEn)) {
                continue;
            }
            $listFr[] = $rowFr;
            $listEn[] = $rowEn;
            $errors += $rowErrors;
        }

        $count = \count($listFr);
        $at = 'list.'.implode('.', $path);
        if ($count < $field->min) {
            $errors[$at] = $fixed
                ? \sprintf('%d éléments attendus.', $field->min)
                : \sprintf('Au moins %d élément%s.', $field->min, $field->min > 1 ? 's' : '');
        } elseif (null !== $field->max && $count > $field->max) {
            $errors[$at] = \sprintf('%d éléments au maximum.', $field->max);
        }

        return [$listFr, $listEn];
    }

    private static function value(Field $field, mixed $raw): mixed
    {
        return match ($field->type) {
            Field::LINES => self::lines($raw),
            Field::NUMBER => self::number($raw),
            default => \is_string($raw) ? trim(str_replace("\r\n", "\n", $raw)) : (\is_int($raw) || \is_float($raw) ? (string) $raw : ''),
        };
    }

    /**
     * @param list<string>          $path
     * @param array<string, string> $errors
     *
     * @param-out array<string, string> $errors
     */
    private static function check(Field $field, mixed $value, string $lang, array $path, array &$errors): void
    {
        $at = $lang.'.'.implode('.', $path);
        if ('' === $value || [] === $value || null === $value) {
            if (!$field->optional) {
                $errors[$at] = match ($lang) {
                    'fr' => 'Texte en français manquant.',
                    'en' => 'Texte en anglais manquant.',
                    default => 'Champ obligatoire.',
                };
            }

            return;
        }
        if (Field::NUMBER === $field->type && !\is_int($value)) {
            $errors[$at] = 'Nombre entier attendu.';
        }
        if (Field::CHOICE === $field->type && !(\is_string($value) && isset($field->choices[$value]))) {
            $errors[$at] = 'Choix invalide.';
        }
    }

    /** @return list<string> */
    private static function lines(mixed $raw): array
    {
        $items = \is_array($raw) ? $raw : explode("\n", \is_string($raw) ? str_replace("\r\n", "\n", $raw) : '');
        $lines = [];
        foreach ($items as $item) {
            $line = \is_string($item) ? trim($item) : '';
            if ('' !== $line) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /** Entier, null si vide, texte d'origine si invalide (réaffiché avec son erreur). */
    private static function number(mixed $raw): int|string|null
    {
        if (\is_int($raw)) {
            return $raw;
        }
        $text = \is_string($raw) ? trim($raw) : '';
        if ('' === $text) {
            return null;
        }

        return preg_match('/^-?\d{1,9}$/', $text) ? (int) $text : $text;
    }

    private static function isEmpty(mixed $value): bool
    {
        if (\is_array($value)) {
            foreach ($value as $item) {
                if (!self::isEmpty($item)) {
                    return false;
                }
            }

            return true;
        }

        return '' === $value || null === $value;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    private static function arr(array $data, int|string $key): array
    {
        $value = $data[$key] ?? [];

        return \is_array($value) ? $value : [];
    }
}

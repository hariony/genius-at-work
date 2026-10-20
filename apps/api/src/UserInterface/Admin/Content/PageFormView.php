<?php

declare(strict_types=1);

namespace App\UserInterface\Admin\Content;

use App\Application\Content\Page\Field;
use App\Application\Content\Page\PageData;
use App\Application\Content\Page\PageDefinition;

/**
 * Arbre prêt à afficher pour le formulaire d'une page : un nœud par champ, avec noms des
 * champs HTML, valeurs et erreurs par langue. Le gabarit admin/content/pages/_node le parcourt.
 * Noms : page[fr][hero][title], page[en][…] ; valeurs communes aux deux langues : page[_][…].
 */
final class PageFormView
{
    /** @return list<array<string, mixed>> */
    public static function build(PageDefinition $page, PageData $data): array
    {
        return self::nodes($page->fields, $data->fr, $data->en, [], $data->errors);
    }

    /**
     * Erreurs des champs, dans l'ordre du formulaire, avec le nom du champ (« Titre (EN) »).
     *
     * @param list<array<string, mixed>> $nodes
     *
     * @return list<array{id: string, label: string, message: string}>
     */
    public static function errorSummary(array $nodes, string $context = ''): array
    {
        $summary = [];
        foreach ($nodes as $node) {
            /** @var string $label */
            $label = $node['label'];
            if ('leaf' === $node['kind']) {
                /** @var list<array{lang: string|null, id: string, error: string|null}> $inputs */
                $inputs = $node['inputs'];
                foreach ($inputs as $input) {
                    if (null !== $input['error']) {
                        $summary[] = ['id' => $input['id'], 'label' => $context.$label.(null === $input['lang'] ? '' : ' ('.$input['lang'].')'), 'message' => $input['error']];
                    }
                }
            } elseif ('list' === $node['kind']) {
                /** @var list<array{number: int, children: list<array<string, mixed>>}> $rows */
                $rows = $node['rows'];
                foreach ($rows as $row) {
                    $summary = [...$summary, ...self::errorSummary($row['children'], $context.$label.' '.$row['number'].' › ')];
                }
            } else {
                /** @var list<array<string, mixed>> $children */
                $children = $node['children'];
                $summary = [...$summary, ...self::errorSummary($children, $context)];
            }
        }

        return $summary;
    }

    /**
     * @param list<Field>           $fields
     * @param array<mixed>          $fr
     * @param array<mixed>          $en
     * @param list<string>          $path
     * @param array<string, string> $errors
     *
     * @return list<array<string, mixed>>
     */
    private static function nodes(array $fields, array $fr, array $en, array $path, array $errors): array
    {
        $nodes = [];
        foreach ($fields as $field) {
            if (null === $field->key) {
                $nodes[] = self::container($field, self::nodes($field->fields, $fr, $en, $path, $errors));
                continue;
            }
            $key = $field->key;
            $here = [...$path, $key];
            $nodes[] = match ($field->type) {
                Field::GROUP => self::container($field, self::nodes($field->fields, self::arr($fr, $key), self::arr($en, $key), $here, $errors)),
                Field::LIST => self::list($field, self::arr($fr, $key), self::arr($en, $key), $here, $errors),
                default => self::leaf($field, $fr[$key] ?? null, $en[$key] ?? null, $here, $errors),
            };
        }

        return $nodes;
    }

    /**
     * @param list<array<string, mixed>> $children
     *
     * @return array<string, mixed>
     */
    private static function container(Field $field, array $children): array
    {
        return [
            'kind' => 'group',
            'label' => $field->label,
            'hint' => $field->hint,
            'children' => $children,
            // Un groupe sans champ direct (que des sous-groupes) ne forme pas de bloc à lui seul.
            'hasFields' => [] !== array_filter($children, static fn (array $c): bool => 'group' !== $c['kind']),
        ];
    }

    /**
     * @param array<mixed>          $fr
     * @param array<mixed>          $en
     * @param list<string>          $path
     * @param array<string, string> $errors
     *
     * @return array<string, mixed>
     */
    private static function list(Field $field, array $fr, array $en, array $path, array $errors): array
    {
        $fixed = null !== $field->max && $field->min === $field->max;
        $count = max(\count($fr), \count($en), $fixed ? $field->min : 0);
        $canAdd = !$fixed && (null === $field->max || $count < $field->max);
        $rows = [];
        for ($i = 0; $i < $count + ($canAdd ? 1 : 0); ++$i) {
            $rows[] = [
                'number' => $i + 1,
                'new' => $i >= $count,
                'children' => self::nodes($field->fields, self::arr($fr, $i), self::arr($en, $i), [...$path, (string) $i], $errors),
            ];
        }

        return [
            'kind' => 'list',
            'label' => $field->label,
            'hint' => $field->hint,
            'fixed' => $fixed,
            'rows' => $rows,
            'error' => $errors['list.'.implode('.', $path)] ?? null,
        ];
    }

    /**
     * @param list<string>          $path
     * @param array<string, string> $errors
     *
     * @return array<string, mixed>
     */
    private static function leaf(Field $field, mixed $fr, mixed $en, array $path, array $errors): array
    {
        $inputs = $field->shared
            ? [self::input('_', null, $fr, $path, $errors)]
            : [self::input('fr', 'FR', $fr, $path, $errors), self::input('en', 'EN', $en, $path, $errors)];

        return [
            'kind' => 'leaf',
            'type' => $field->type,
            'label' => $field->label,
            'hint' => $field->hint,
            'optional' => $field->optional,
            'rows' => $field->rows,
            'choices' => $field->choices,
            'inputs' => $inputs,
        ];
    }

    /**
     * @param list<string>          $path
     * @param array<string, string> $errors
     *
     * @return array{lang: string|null, name: string, id: string, value: string, error: string|null}
     */
    private static function input(string $lang, ?string $label, mixed $value, array $path, array $errors): array
    {
        $at = $lang.'.'.implode('.', $path);

        return [
            'lang' => $label,
            'name' => 'page['.$lang.']['.implode('][', $path).']',
            'id' => 'f-'.str_replace(['.', '_'], '-', $at),
            'value' => match (true) {
                \is_array($value) => implode("\n", array_filter($value, is_string(...))),
                \is_string($value), \is_int($value) => (string) $value,
                default => '',
            },
            'error' => $errors[$at] ?? null,
        ];
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

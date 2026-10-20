<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\Content\Page\Field;
use App\Application\Content\Page\PageCatalog;
use App\Application\Content\Page\PageDefinition;
use App\Application\Content\Page\PageNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PageCatalogTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function seedPages(): iterable
    {
        foreach (array_keys(self::seed()) as $id) {
            yield $id => [$id];
        }
    }

    public function testEveryPageOfTheCatalogHasAnInitialContent(): void
    {
        self::assertEqualsCanonicalizing(array_keys(PageCatalog::all()), array_keys(self::seed()));
    }

    /** Aucun texte du contenu initial n'est ignoré ou transformé par la description de la page. */
    #[DataProvider('seedPages')]
    public function testInitialContentMatchesItsDescriptionExactly(string $id): void
    {
        $page = PageCatalog::get($id);
        self::assertInstanceOf(PageDefinition::class, $page);
        $texts = self::seed()[$id];
        $data = PageNormalizer::fromStored($page, $texts['fr'], $texts['en']);

        self::assertSame([], $data->errors);
        self::assertEquals($texts['fr'], $data->fr);
        self::assertEquals($texts['en'], $data->en);
    }

    public function testMissingTranslationsAndEmptyRowsAreHandled(): void
    {
        $page = new PageDefinition('test', 'Test', PageCatalog::PAGES, '', null, [
            Field::line('title', 'Titre'),
            Field::list('items', 'Éléments', [Field::line('label', 'Libellé'), Field::number('value', 'Nombre')]),
        ]);
        $data = PageNormalizer::normalize(
            $page,
            ['title' => '  Bonjour ', 'items' => [['label' => 'Un'], ['label' => ''], ['label' => 'Trois']]],
            ['title' => '', 'items' => [['label' => 'One'], ['label' => ''], ['label' => 'Three']]],
            ['items' => [['value' => '1'], ['value' => ''], ['value' => 'x']]],
        );

        self::assertSame('Bonjour', $data->fr['title']);
        self::assertSame([['label' => 'Un', 'value' => 1], ['label' => 'Trois', 'value' => 'x']], $data->fr['items'], 'Ligne vide retirée.');
        self::assertSame([
            'en.title' => 'Texte en anglais manquant.',
            '_.items.1.value' => 'Nombre entier attendu.',
        ], $data->errors);
    }

    /** @return array<string, array{fr: array<string, mixed>, en: array<string, mixed>}> */
    private static function seed(): array
    {
        /** @var array{pages: array<string, array{fr: array<string, mixed>, en: array<string, mixed>}>} $seed */
        $seed = json_decode((string) file_get_contents(\dirname(__DIR__, 3).'/data/content-seed.json'), true, flags: \JSON_THROW_ON_ERROR);

        return $seed['pages'];
    }
}

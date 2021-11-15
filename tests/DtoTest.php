<?php

declare(strict_types=1);

namespace kuaukutsu\dto\tests;

use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use kuaukutsu\dto\tests\stub\ModelDto;

final class DtoTest extends TestCase
{
    /**
     * @dataProvider dataProviderHydrate()
     * @param array<string, mixed> $data
     * @param string[] $map
     * @param array<string, mixed> $expected
     */
    public function testHydrate(array $data, array $map, array $expected): void
    {
        $dto = ModelDto::hydrate($data, $map);
        $dataDto = $dto->toArray();

        foreach ($expected as $key => $value) {
            // проверка, что возвращаемых свойств через toArray() столько же, сколько объявлено в map, если задано
            if (count($map) > 0) {
                self::assertCount(count($map), $dataDto);
            }

            // проверка, что hydrate верно отработал и в массиве DTO заданные данные
            self::assertEquals($value, $dataDto[$key]);

            // проверка, что объект DTO имеет верные значения
            self::assertEquals($value, $dto->{$key});
        }
    }

    /**
     * Проверка, что в toArray только те свойства, которые были поулчены через data.
     *
     * @throws Exception
     */
    public function testHydrateEmptyMap(): void
    {
        $dto = ModelDto::hydrate(['id' => 6, 'name' => 'NameHydrate', 'unknown' => 123], []);

        $data = $dto->toArray();

        // должны быть два найденных свойства: id, name
        self::assertCount(2, $data);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('name', $data);
    }

    /**
     * Проверка что в toArray будет сохранен порядок переданый в массиве $fields
     *
     * @throws Exception
     */
    public function testMapFieldSort(): void
    {
        $dto = ModelDto::hydrate(['id' => 6, 'name' => 'NameHydrate', 'tree' => 'tree']);

        // если fields заданы
        self::assertEquals(['name', 'id', 'tree'], array_keys($dto->toArray(['name', 'id', 'tree'])));

        // если НЕ передан массив $fields, то порядок берёться из DTO.
        self::assertEquals(['id', 'name', 'tree'], array_keys($dto->toArray()));
    }

    /**
     * Проверяем, что через второй параметр $map,
     * можем задавать список fileds которые хотим видеть в конечном массиве.
     */
    public function testFillFieldsUsedInMap(): void
    {
        $dto = ModelDto::hydrate(['id' => 6, 'name' => 'NameHydrate', 'tree' => 'tree']);

        // camelCase должен быть в списке со значением по умолчанию
        self::assertEquals(
            ['id', 'name', 'camelCase'],
            array_keys($dto->toArray(['id', 'name', 'camelCase']))
        );
    }

    /**
     * Проверка, что для необъявленных свойств, значения по умолчанию выставляются верно.
     */
    public function testDefaultValue(): void
    {
        $dto = ModelDto::hydrate(['id' => 6, 'name' => 'NameHydrate']);

        self::assertEquals([], $dto->props);
        self::assertEquals(null, $dto->tree);
    }

    /**
     * Проверка, что переданное значение NULL верно воспринимается системой.
     */
    public function testValueNull(): void
    {
        $dto = ModelDto::hydrate(['id' => 6, 'name' => null]);
        $array = $dto->toArray();

        self::assertCount(2, $array);
        self::assertArrayHasKey('id', $array);
        self::assertArrayHasKey('name', $array);
    }

    public function dataProviderHydrate(): array
    {
        return [
            // Простая проверка, что только заданные значения задаются
            [
                ['id' => 1, 'name' => 'NameHydrate', 'props' => [1, 2, 3]],
                ['id', 'name'],
                ['id' => 1, 'name' => 'NameHydrate']
            ],
            [
                ['id' => 3, 'name' => 'NameHydrate', 'props' => [1, 2, 3]],
                ['id', 'props'],
                ['id' => 3, 'props' => [1, 2, 3]]
            ],
            // Проверка, что работает xpath
            [
                ['id' => 2, 'path' => ['name' => 'PathNameHydrate']],
                ['id', 'name' => 'path.name'],
                ['id' => 2, 'name' => 'PathNameHydrate']
            ],
            [
                ['id' => 4, 'path' => ['sub' => ['name' => 'PathSubNameHydrate']]],
                ['id', 'name' => 'path.sub.name'],
                ['id' => 4, 'name' => 'PathSubNameHydrate']
            ],
            // Если map пустой массив, то данные берутся из DTO.
            [
                ['id' => 6, 'name' => 'NameHydrate', 'unknown' => 123],
                [],
                ['id' => 6, 'name' => 'NameHydrate']
            ],
            // Проверяем, что через fields можно управлять данными через Closure
            [
                ['id' => 7, 'name' => 'NameHydrate', 'props' => [1, 2, 3]],
                ['id', 'name', 'props' => fn(array $data): array => (array)($data['props'] ?? [])],
                ['id' => 7, 'name' => 'NameHydrate', 'props' => [1, 2, 3]]
            ],
            [
                ['id' => 8, 'name' => 'NameHydrate', 'props' => [1, 2, 3]],
                ['id' => 'id', 'name' => 'name', 'props' => fn(): array => []],
                ['id' => 8, 'name' => 'NameHydrate', 'props' => []]
            ],
            // Проверяем, что в toArray будут только те свойства, которые явно переданы в map или имеют значение
            [
                ['id' => 7, 'name' => null, 'tree' => null],
                ['id', 'name'],
                ['id' => 7, 'name' => null]
            ],
        ];
    }
}

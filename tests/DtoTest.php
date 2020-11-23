<?php

namespace kuaukutsu\dto\tests;

use kuaukutsu\dto\tests\stub\ClassicDto;
use kuaukutsu\dto\tests\stub\ModelDto;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;

class DtoTest extends TestCase
{
    /**
     * @dataProvider dataProviderHydrate()
     * @param array<string, mixed> $data
     * @param string[] $map
     * @param array<string, mixed> $expected
     * @throws ReflectionException
     */
    public function testHydrate(array $data, array $map, array $expected): void
    {
        $object = ModelDto::hydrate($data, $map);
        $dataDto = $object->toArray();

        $classicDto = ClassicDto::hydrate($data, $map);
        $dataClassicDto = $classicDto->toArray();


        foreach ($expected as $key => $value) {
            // проверка что возвращаемых свойств через toArray() столько же, сколько объявлено в map, если задано
            if (count($map)) {
                self::assertCount(count($map), $dataDto);
                self::assertCount(count($map), $dataClassicDto);
            }

            // проверка что hydrate верно отработал и в массиве DTO заданные данные
            self::assertEquals($value, $dataDto[$key]);
            self::assertEquals($value, $dataClassicDto[$key]);

            // проверка что объект DTO имеет верные значения
            self::assertEquals($value, $object->{'get' . $key}());
            self::assertEquals($value, $classicDto->{$key});
        }
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function testHydrateEmptyMap(): void
    {
        $object = ModelDto::hydrate(['id' => 6, 'name' => 'NameHydrate', 'unknown' => 123], []);

        $data = $object->toArray();

        // должны быть два найденных свойства: id, name
        self::assertCount(2, $data);
        self::assertArrayHasKey('id', $data);
        self::assertArrayHasKey('name', $data);
    }

    public function dataProviderHydrate(): array
    {
        return [
            [
                ['id' => 1, 'name' => 'NameHydrate', 'props' => [1,2,3]],
                ['id', 'name'],
                ['id' => 1, 'name' => 'NameHydrate']
            ],
            [
                ['id' => 2, 'path' => ['name' => 'PathNameHydrate']],
                ['id', 'name' => 'path.name'],
                ['id' => 2, 'name' => 'PathNameHydrate']
            ],
            [
                ['id' => 3, 'name' => 'NameHydrate', 'props' => [1,2,3]],
                ['id', 'props'],
                ['id' => 3, 'props' => [1,2,3]]
            ],
            [
                ['id' => 4, 'path' => ['sub' => ['name' => 'PathSubNameHydrate']]],
                ['id', 'name' => 'path.sub.name'],
                ['id' => 4, 'name' => 'PathSubNameHydrate']
            ],
            [
                ['id' => 5, 'name' => 'NameHydrate'],
                ['id', 'name'],
                ['id' => 5, 'name' => 'NameHydrate']
            ],
            [
                ['id' => 6, 'name' => 'NameHydrate', 'unknown' => 123],
                [],
                ['id' => 6, 'name' => 'NameHydrate']
            ],
            [
                ['id' => 7, 'name' => 'NameHydrate', 'props' => [1,2,3]],
                ['id', 'name', 'props' => fn(array $data): array => (array)($data['props'] ?? [])],
                ['id' => 7, 'name' => 'NameHydrate', 'props' => [1,2,3]]
            ],
            [
                ['id' => 8, 'name' => 'NameHydrate', 'props' => [1,2,3]],
                ['id' => 'id', 'name' => 'name', 'props' => fn(array $data): array => []],
                ['id' => 8, 'name' => 'NameHydrate', 'props' => []]
            ],
        ];
    }
}

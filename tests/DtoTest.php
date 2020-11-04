<?php

namespace kuaukutsu\dto\tests;

use PHPUnit\Framework\TestCase;
use ReflectionException;

class DtoTest extends TestCase
{
    /**
     * @dataProvider dataProviderHydrate()
     * @param array<array-key, mixed> $data
     * @param string[] $map
     * @param array<string, mixed> $expected
     * @throws ReflectionException
     */
    public function testHydrate(array $data, array $map, array $expected): void
    {
        $object = ModelDto::hydrate($data, $map);

        foreach ($expected as $key => $value) {
            $data = $object->toArray();

            // проверка что возвращаемых свойств через toArray() столько же, сколько объявлено в map
            self::assertCount(count($map), $data);

            // проверка что hydrate верно отработал и в массиве DTO заданные данные
            self::assertEquals($value, $data[$key]);

            // проверка что объект DTO имеет верные значения
            self::assertEquals($value, $object->{'get' . $key}());
        }
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
        ];
    }
}

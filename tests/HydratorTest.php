<?php

namespace kuaukutsu\dto\tests;

use kuaukutsu\dto\Hydrator;
use PHPUnit\Framework\TestCase;
use ReflectionException;

class HydratorTest extends TestCase
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
        $hydrator = new Hydrator($map);

        /** @var ModelDto $object */
        $object = $hydrator->hydrate($data, ModelDto::class);

        foreach ($expected as $key => $value) {
            // проверка что объект DTO имеет верные значения
            self::assertEquals($value, $object->{'get' . $key}());
        }
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateEmptyMap(): void
    {
        $hydrator = new Hydrator([]);

        /** @var ModelDto $object */
        $object = $hydrator->hydrate(['id' => 5, 'name' => 'NameHydrate'], ModelDto::class);

        self::assertEmpty($object->toArray());
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
                ['id' => 5, 'name' => 'NameHydrate', 'props' => []]
            ],
        ];
    }
}

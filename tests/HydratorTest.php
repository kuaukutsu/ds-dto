<?php

declare(strict_types=1);

namespace kuaukutsu\ds\dto\tests;

use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use kuaukutsu\ds\dto\tests\stub\ModelDto;
use kuaukutsu\ds\dto\Hydrator;

final class HydratorTest extends TestCase
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
            // проверка, что объект DTO имеет верные значения
            self::assertEquals($value, $object->{$key});
        }
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     * @throws InvalidArgumentException
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
            // Простая проверка, что только заданные значения задаются
            [
                ['id' => 1, 'name' => 'NameHydrate', 'props' => [1, 2, 3]],
                ['id', 'name'],
                ['id' => 1, 'name' => 'NameHydrate'],
            ],
            [
                ['id' => 3, 'name' => 'NameHydrate', 'props' => [1, 2, 3]],
                ['id', 'props'],
                ['id' => 3, 'props' => [1, 2, 3]],
            ],
            // Проверка, что работает xpath
            [
                ['id' => 2, 'path' => ['name' => 'PathNameHydrate']],
                ['id', 'name' => 'path.name'],
                ['id' => 2, 'name' => 'PathNameHydrate'],
            ],
            [
                ['id' => 4, 'path' => ['sub' => ['name' => 'PathSubNameHydrate']]],
                ['id', 'name' => 'path.sub.name'],
                ['id' => 4, 'name' => 'PathSubNameHydrate'],
            ],
            // Проверка значений по умолчанию
            [
                ['id' => 5, 'name' => 'NameHydrate'],
                ['id', 'name'],
                ['id' => 5, 'name' => 'NameHydrate', 'props' => []],
            ],
            // Проверяем, что если в данных snake_case, то пробуем привести его к виду camelCase
            [
                ['id' => 6, 'camel_case' => 'test'],
                ['id', 'camelCase'],
                ['id' => 6, 'camelCase' => 'test'],
            ],
        ];
    }
}

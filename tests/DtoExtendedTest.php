<?php

declare(strict_types=1);

namespace kuaukutsu\ds\dto\tests;

use PHPUnit\Framework\TestCase;
use kuaukutsu\ds\dto\tests\stub\ModelDto;
use kuaukutsu\ds\dto\tests\stub\ModelExtendedDto;

final class DtoExtendedTest extends TestCase
{
    /**
     * @dataProvider dataProviderHydrate()
     * @param array<string, mixed> $data
     * @param string[] $map
     * @param array<string, mixed> $expected
     */
    public function testHydrate(array $data, array $map, array $expected): void
    {
        $object = ModelExtendedDto::hydrate($data, $map);
        $dataDto = $object->toArrayRecursive();

        foreach ($expected as $key => $value) {
            // проверка, что возвращаемых свойств через toArray() столько же, сколько объявлено в map, если задано
            if (count($map)) {
                self::assertCount(count($map), $dataDto);
            }

            // проверка, что hydrate верно отработал и в массиве DTO заданные данные
            self::assertEquals($value, $dataDto[$key]);
        }
    }

    public function testNestedDtoAutoType(): void
    {
        $dto = ModelExtendedDto::hydrate(
            [
                'id' => 11,
                'modelDto' => [
                    'id' => 112,
                    'name' => 'nested dto',
                ],
                'modelExtendedDto' => [
                    'id' => 22,
                    // possibly array
                    'modelDto' => [
                        'id' => 222,
                        'name' => 'nested dto 2',
                    ],
                    // possibly DTO
                    'modelSecondDto' => ModelDto::hydrate(['id' => 233, 'name' => 'Second DTO']),
                ],
            ]
        );

        // type check
        self::assertInstanceOf(ModelDto::class, $dto->modelDto);
        // data check
        self::assertEquals(112, $dto->modelDto->id);
        self::assertEquals('nested dto', $dto->modelDto->name);

        // nested type check
        self::assertInstanceOf(ModelExtendedDto::class, $dto->modelExtendedDto);
        self::assertInstanceOf(ModelDto::class, $dto->modelExtendedDto->modelDto);
        // nested data check
        self::assertEquals(222, $dto->modelExtendedDto->modelDto->id);
        self::assertEquals('nested dto 2', $dto->modelExtendedDto->modelDto->name);
    }

    public static function dataProviderHydrate(): array
    {
        return [
            [
                ['id' => 1, 'modelDto' => ModelDto::hydrate(['id' => 11, 'name' => 'test'])],
                ['id', 'modelDto'],
                ['id' => 1, 'modelDto' => ['id' => 11, 'name' => 'test']],
            ],
            [
                [
                    'id' => 2,
                    'modelDto' => ModelDto::hydrate(['id' => 22, 'name' => 'test']),
                    'modelExtendedDto' => ModelExtendedDto::hydrate(
                        [
                            'id' => 22,
                            'modelDto' => ModelDto::hydrate(['id' => 222, 'name' => 'test222']),
                        ]
                    ),
                ],
                ['id', 'modelDto', 'modelExtendedDto'],
                [
                    'id' => 2,
                    'modelDto' => [
                        'id' => 22,
                        'name' => 'test',
                    ],
                    'modelExtendedDto' => [
                        'id' => 22,
                        'modelDto' => [
                            'id' => 222,
                            'name' => 'test222',
                        ],
                    ],
                ],
            ],
        ];
    }
}

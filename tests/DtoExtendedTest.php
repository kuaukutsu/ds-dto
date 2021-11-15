<?php

declare(strict_types=1);

namespace kuaukutsu\dto\tests;

use PHPUnit\Framework\TestCase;
use kuaukutsu\dto\tests\stub\ModelDto;
use kuaukutsu\dto\tests\stub\ModelExtendedDto;

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

    public function dataProviderHydrate(): array
    {
        return [
            [
                ['id' => 1, 'modelDto' => ModelDto::hydrate(['id' => 11, 'name' => 'test'])],
                ['id', 'modelDto'],
                ['id' => 1, 'modelDto' => ['id' => 11, 'name' => 'test']]
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
                    )
                ],
                ['id', 'modelDto', 'modelExtendedDto'],
                [
                    'id' => 2,
                    'modelDto' => [
                        'id' => 22,
                        'name' => 'test'
                    ],
                    'modelExtendedDto' => [
                        'id' => 22,
                        'modelDto' => [
                            'id' => 222,
                            'name' => 'test222'
                        ]
                    ]
                ]
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace kuaukutsu\dto\tests;

use PHPUnit\Framework\TestCase;
use kuaukutsu\dto\tests\stub\ModelDtoCollection;
use kuaukutsu\dto\tests\stub\ModelExtendedDto;

final class CollectionTest extends TestCase
{
    public function testCollectionAutoType(): void
    {
        $dto = ModelExtendedDto::hydrate(
            [
                'id' => 1,
                'collection' => [
                    [
                        'id' => 11,
                        'name' => 'first item',
                    ],
                    [
                        'id' => 12,
                        'name' => 'second item',
                    ]
                ],
            ]
        );

        // type check
        self::assertInstanceOf(ModelDtoCollection::class, $dto->collection);
        // data check
        self::assertCount(2, $dto->collection);

        foreach ($dto->collection as $item) {
            self::assertInstanceOf($dto->collection->getType(), $item);
        }

        $item = $dto->collection->get(12);
        self::assertNotEmpty($item);
        self::assertEquals(12, $item->id);
    }
}

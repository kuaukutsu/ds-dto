<?php

declare(strict_types=1);

namespace kuaukutsu\ds\dto\tests\stub;

use kuaukutsu\ds\dto\DtoBase;

/**
 * @psalm-immutable
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ModelExtendedDto extends DtoBase
{
    public ?int $id = null;

    public ?ModelDto $modelDto = null;

    public ?ModelDto $modelSecondDto = null;

    public ?ModelExtendedDto $modelExtendedDto = null;

    public ?ModelDtoCollection $collection = null;

    public ?ModelExtendedDtoCollection $extendedCollection = null;
}

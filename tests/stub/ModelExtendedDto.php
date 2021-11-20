<?php

declare(strict_types=1);

namespace kuaukutsu\dto\tests\stub;

use kuaukutsu\dto\DtoBase;

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
}

<?php

declare(strict_types=1);

namespace kuaukutsu\ds\dto\tests\stub;

use kuaukutsu\ds\dto\DtoBase;

/**
 * @psalm-immutable
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ModelDto extends DtoBase
{
    public ?int $id = null;

    public ?string $name = null;

    public ?string $tree = null;

    public ?string $camelCase = null;

    public array $props = [];
}

<?php

declare(strict_types=1);

namespace kuaukutsu\dto\tests\stub;

use kuaukutsu\dto\DtoBase;

/**
 * Class ClassicDto
 * @psalm-immutable
 */
final class ClassicDto extends DtoBase
{
    public ?int $id = null;

    public ?string $name = null;

    public ?array $props = [];

    public ?string $tree = null;
}

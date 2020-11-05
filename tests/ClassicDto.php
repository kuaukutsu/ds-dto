<?php
declare(strict_types=1);

namespace kuaukutsu\dto\tests;

use kuaukutsu\dto\BaseDto;

/**
 * Class ClassicDto
 * @psalm-immutable
 */
final class ClassicDto extends BaseDto
{
    public ?int $id = null;

    public ?string $name = null;

    public ?array $props = [];

    public ?string $tree = null;
}

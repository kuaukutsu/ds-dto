<?php

declare(strict_types=1);

namespace kuaukutsu\dto\tests\stub;

use kuaukutsu\ds\collection\Collection;

final class ModelExtendedDtoCollection extends Collection
{
    public function getType(): string
    {
        return ModelExtendedDto::class;
    }
}

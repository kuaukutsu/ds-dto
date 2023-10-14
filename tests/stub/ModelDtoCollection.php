<?php

declare(strict_types=1);

namespace kuaukutsu\dto\tests\stub;

use kuaukutsu\ds\collection\Collection;

final class ModelDtoCollection extends Collection
{
    public function getType(): string
    {
        return ModelDto::class;
    }

    /**
     * @param ModelDto $item
     * @return int
     */
    protected function indexBy($item): int
    {
        return $item->id;
    }
}

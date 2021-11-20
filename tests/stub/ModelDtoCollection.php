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
     * @param ModelDto|object $item
     * @return int
     * @psalm-suppress MixedReturnStatement
     */
    protected function indexBy(object $item): int
    {
        return $item->id;
    }
}

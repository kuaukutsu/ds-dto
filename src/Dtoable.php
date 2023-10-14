<?php

declare(strict_types=1);

namespace kuaukutsu\ds\dto;

/**
 * Is the interface that should be implemented by classes who want to support DTO representation of their instances.
 */
interface Dtoable
{
    public function toDto(): DtoInterface;
}

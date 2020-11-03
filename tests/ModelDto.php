<?php
declare(strict_types=1);

namespace kuaukutsu\dto\tests;

use kuaukutsu\dto\BaseDto;

/**
 * Class ModelDto
 * @psalm-immutable
 */
final class ModelDto extends BaseDto
{
    protected int $id;

    protected string $name;

    protected array $props = [];

    protected ?string $tree;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getProps(): array
    {
        return $this->props ?? [];
    }

    /**
     * @return string|null
     */
    public function getTree(): ?string
    {
        return $this->tree;
    }
}

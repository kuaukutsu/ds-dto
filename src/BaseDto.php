<?php

namespace kuaukutsu\dto;

use ReflectionException;

/**
 * Class BaseDto
 *
 * Базовый класс для объекта DTO.
 * DTO простой класс для обмена данными между компонентами.
 * Не должно быть никакой бизнес логики.
 *
 * @psalm-immutable
 */
abstract class BaseDto implements DtoInterface
{
    /**
     * @var string[] имена свойств которые участвуют в мапинге
     */
    private array $fieldsUsedInMap = [];

    /**
     * Construct.
     *
     * @param array<string, mixed> $data данные которыми необходимо заполнить экземпляр объекта
     * @param string[]|array<string, string> $map по умолчанию будет генерироваться на основе полей DTO
     * @return static
     * @throws ReflectionException
     */
    public static function hydrate(array $data, array $map = []): DtoInterface
    {
        if ($map === []) {
            $map = array_keys(get_class_vars(static::class));
        }

        /** @var static $model */
        $model = (new Hydrator($map))->hydrate($data, static::class);

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $fields = []): array
    {
        if ($fields === []) {
            $fields = $this->getFieldsUsedInMap();
        }

        return array_filter(get_object_vars($this), static function (string $key) use ($fields): bool {
            return in_array($key, $fields, true);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @return string[] имена свойств которые участвуют в мапинге
     */
    protected function getFieldsUsedInMap(): array
    {
        return $this->fieldsUsedInMap;
    }
}

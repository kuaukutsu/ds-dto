<?php

namespace kuaukutsu\dto;

use ReflectionException;

/**
 * Class BaseDto
 *
 * Базовый класс для объекта DTO.
 * DTO простой класс для обмена данными между компонентами.
 * Не должно быть никакой бизнес логики.
 */
abstract class BaseDto implements DtoInterface
{
    /**
     * @var array имена свойств которые участвуют в мапинге
     */
    private array $fieldsUsedInMaps = [];

    /**
     * Construct.
     *
     * @param array $data данные которыми необходимо заполнить экземпляр объекта
     * @param array<array-key, string> $map по умолчанию будет генерироваться на основе полей DTO
     * @return static
     * @throws ReflectionException
     */
    public static function hydrate(array $data, array $map = []): DtoInterface
    {
        if ($map === []) {
            $map = array_keys(get_class_vars(static::class));
        }

        $hydrator = new Hydrator($map);

        /** @var static $model */
        $model = $hydrator->hydrate($data, static::class);
        $model->fieldsUsedInMaps = $hydrator->getFields();

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function toArray(array $fields = []): array
    {
        if ($fields === []) {
            $fields = $this->fieldsUsedInMaps;
        }

        return array_filter(get_object_vars($this), static function (string $key) use ($fields): bool {
            return in_array($key, $fields, true);
        }, ARRAY_FILTER_USE_KEY);
    }
}

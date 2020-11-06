<?php

namespace kuaukutsu\dto;

use ReflectionClass;
use ReflectionException;

/**
 * Class Hydrator
 *
 * Example:
 *
 * ```php
 * $data = [];
 *
 * $dtoHydrator = new Hydrator([
 *  'id' => 'guid',
 *  'name' => 'owner.0._name',
 *  'parent_id' => 'parent.id',
 * ]);
 *
 * $item = $dtoHydrator->hydrate($data, ModelDTO::class);
 * ```
 *
 */
final class Hydrator
{
    /**
     * Mapping
     *
     * @var array<string, string|callable> массив пересечения схем между насыщаемым объектов и данными.
     */
    private array $map;

    /**
     * @var string[] массив свойств объекта которые были найдены в массиве данных.
     */
    private array $fields = [];

    /**
     * @var string случайная строка, примесь
     */
    private string $hashStub;

    /**
     * Hydrator constructor.
     *
     * @param string[]|array<string, string> $map может быть:
     * - ассоциативным массивом (слева: свойство объекта; справа: путь до данных в массиве)
     * - плоским массивом, тогда считам что свойства объекта, есть и путь до данных в массиве
     */
    public function __construct(array $map)
    {
        $this->map = [];
        foreach ($map as $keyTo => $keyFrom) {
            if (is_int($keyTo)) {
                $keyTo = $keyFrom;
            }

            $this->map[$keyTo] = $keyFrom;
        }

        // случайный hash
        $this->hashStub = hash('crc32', serialize($map));
    }

    /**
     * @param array<string, mixed> $data массив с данными
     * @param string $className
     * @psalm-param class-string $className имя класса, на основе которого будет создан объект
     * @return object
     * @throws ReflectionException
     */
    public function hydrate(array $data, string $className): object
    {
        $reflection = new ReflectionClass($className);
        $object = $reflection->newInstanceWithoutConstructor();
        foreach ($this->map as $dataKey => $propertyValue) {
            if ($reflection->hasProperty($dataKey)) {
                $property = $reflection->getProperty($dataKey);
                $property->setAccessible(true);
                $property->setValue($object, $this->getValue($dataKey, $propertyValue, $data));
            }
        }

        /**
         * Применимо к DTO, получаем список явно полученных свойств из массива данных,
         * и передаём в приватное свойство абстрактоного класса BaseDTO.
         * Можно получать те же данные явно, через publick getFields().
         */
        if (($parent = $reflection->getParentClass()) && $parent->hasProperty('fieldsUsedInMap')) {
            $property = $parent->getProperty('fieldsUsedInMap');
            $property->setAccessible(true);
            $property->setValue($object, $this->getFields());
        }

        return $object;
    }

    /**
     * @return string[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Получаем значение из массива данных.
     *
     * @param string $key
     * @param string|callable $value
     * @param array<string, mixed> $data
     * @param mixed $default
     * @return mixed
     */
    private function getValue(string $key, $value, array $data, $default = null)
    {
        if (is_callable($value)) {
            $this->fields[] = $key;
            return $value($data) ?? $default;
        }

        /**
         * Фокус: если по обычному ключу в массиве данных нет значений или null,
         * то пробуем найти ключ (изменить на camelCase и поискать ещё раз),
         * либо ключ найден и тогда мы вернём значение, либо нет, и тогда вернём хэш заглушку,
         * тем самым отмечаем что ключ массива соответсвует свойству, либо не найден.
         */
        $valueHash = self::getValueByPath($data, $value, $this->hashStub);

        if ($valueHash !== $this->hashStub) {
            $this->fields[] = $key;
            return $valueHash;
        }

        return $default;
    }

    /**
     * Example: getValueByPath(Data[], 'key.subkey')
     *
     * @param array<string, mixed> $array
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    private static function getValueByPath(array $array, string $path, $default = null)
    {
        $key = trim($path, '.');
        $keyArr = explode('.', $key);

        if (count($keyArr) > 1) {
            foreach ($keyArr as $name) {
                if (!isset($array[$name])) {
                    return $default;
                }

                $array = $array[$name] ?? [];
            }

            return $array;
        }

        return $array[$key] ?? $default;
    }
}

<?php

declare(strict_types=1);

namespace kuaukutsu\ds\dto;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use TypeError;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Strings\Inflector;
use kuaukutsu\ds\collection\Collection;

/**
 * Hydrator
 *
 * @example
 *
 * ```php
 * $data = [];
 *
 * $dtoHydrator = new Hydrator([
 *  'id' => 'guid',
 *  'name' => 'owner.0._name',
 *  'parent_id' => 'parent.id',
 *  'props' => static fn(array $inputData) => $inputData['props'] ?? [],
 * ]);
 *
 * $item = $dtoHydrator->hydrate($data, ModelDTO::class);
 * ```
 *
 * @template T of DtoInterface
 */
final class Hydrator
{
    /**
     * Mapping
     *
     * @var array<string, string|Closure> Массив пересечения схем между насыщаемым объектов и данными.
     */
    private readonly array $map;

    /**
     * @var string Случайная строка, примесь
     */
    private readonly string $hashStub;

    /**
     * @var string[] Массив свойств объекта которые были найдены в массиве данных.
     */
    private array $fields = [];

    /**
     * @var Inflector|null Для преобразования строки pascalCaseToId
     */
    private ?Inflector $inflector = null;

    /**
     * Hydrator constructor.
     *
     * @param string[]|array<string, string|Closure> $map Может быть:
     * - ассоциативным массивом (слева: свойство объекта; справа: путь до данных в массиве)
     * - плоским массивом, тогда считам что свойства объекта, есть и путь до данных в массиве
     * @throws TypeError
     */
    public function __construct(array $map)
    {
        $this->map = $this->generateMap($map);
        $this->hashStub = microtime() . '619a799747d348fa1caf181a72b65d9f';
    }

    /**
     * @param array<string, mixed> $data Массив с данными
     * @param class-string<T> $className Имя класса, на основе которого будет создан объект
     * @return DtoInterface
     * @throws ReflectionException
     */
    public function hydrate(array $data, string $className): DtoInterface
    {
        $reflection = new ReflectionClass($className);

        /** @var DtoInterface $object */
        $object = $reflection->newInstanceWithoutConstructor();
        $default = $reflection->getDefaultProperties();

        foreach ($this->map as $name => $propertyValue) {
            if ($reflection->hasProperty($name)) {
                $property = $reflection->getProperty($name);

                $value = $this->getValue($name, $propertyValue, $data, $default[$name] ?? null);
                if (is_array($value)) {
                    $autoCastValue = null;
                    // is associative?
                    if (is_string(array_key_first($value))) {
                        /** @var array<string, mixed> $value */
                        $autoCastValue = $this->tryCastToDto($property, $value);
                    }

                    if ($autoCastValue === null) {
                        $autoCastValue = $this->tryCastToCollection($property, $value);
                    }

                    if ($autoCastValue !== null) {
                        $value = $autoCastValue;
                    }
                }

                $property->setValue($object, $value);
            }
        }

        /**
         * Применимо к DTO, получаем список явно полученных свойств из массива данных,
         * и передаём в приватное свойство абстрактоного класса BaseDTO.
         * Можно получать те же данные явно, через public getFields().
         */
        $parent = $reflection->getParentClass();
        if ($parent !== false && $parent->hasProperty('fieldsUsedInMap')) {
            $property = $parent->getProperty('fieldsUsedInMap');
            $property->setValue($object, $this->fields);
        }

        return $object;
    }

    /**
     * @param string[]|array<string, string|Closure> $map Может быть:
     * - ассоциативным массивом (слева: свойство объекта; справа: путь до данных в массиве)
     * - плоским массивом, тогда считам что свойства объекта, есть и путь до данных в массиве
     * @return array<string, string|Closure>
     * @throws TypeError
     */
    private function generateMap(array $map): array
    {
        $prepareMap = [];
        foreach ($map as $keyTo => $keyFrom) {
            if (is_int($keyTo)) {
                if (is_string($keyFrom) === false) {
                    throw new TypeError('Array item must be a string.');
                }

                $keyTo = $keyFrom;
            }

            $prepareMap[$keyTo] = $keyFrom;
        }

        return $prepareMap;
    }

    /**
     * Получаем значение из массива данных.
     *
     * @param Closure|string $value
     */
    private function getValue(string $name, mixed $value, array $data, mixed $default = null): mixed
    {
        if ($value instanceof Closure) {
            $this->fields[] = $name;
            return $value($data);
        }

        /**
         * Фокус: если по обычному ключу в массиве данных нет значений,
         * то пробуем найти ключ в данных (изменить на snake_case и поискать ещё раз),
         * Если ключ найден - вернём значение, если нет, то вернём хэш заглушку (свойство не определено).
         * fieldsUsedInMap - карта действительно переданных свойств в data.
         */
        $propertyValue = ArrayHelper::getValueByPath($data, $value, $this->hashStub);
        if ($propertyValue === $this->hashStub) {
            $propertyValue = ArrayHelper::getValueByPath(
                $data,
                $this->getInflector()->pascalCaseToId($value, '_'),
                $this->hashStub
            );
        }

        if ($propertyValue !== $this->hashStub) {
            $this->fields[] = $name;
            return $propertyValue;
        }

        return $default;
    }

    private function getInflector(): Inflector
    {
        if ($this->inflector === null) {
            $this->inflector = (new Inflector())->withoutIntl();
        }

        return $this->inflector;
    }

    /**
     * @param array<string, mixed> $value
     */
    private function tryCastToDto(ReflectionProperty $property, array $value): ?DtoInterface
    {
        /** @var ReflectionNamedType|null $type */
        $type = $property->getType();
        if ($type === null) {
            return null;
        }

        $className = $type->getName();
        if (is_subclass_of($className, DtoInterface::class)) {
            return $className::hydrate($value);
        }

        return null;
    }

    private function tryCastToCollection(ReflectionProperty $property, array $value): ?Collection
    {
        /** @var ReflectionNamedType|null $type */
        $type = $property->getType();
        if ($type === null) {
            return null;
        }

        $className = $type->getName();
        if (is_subclass_of($className, Collection::class)) {
            $collection = new $className();
            $collectionType = $collection->getType();
            if (is_subclass_of($collectionType, DtoInterface::class) === false) {
                return null;
            }

            foreach ($value as $item) {
                if (is_array($item) === false || is_string(array_key_first($item)) === false) {
                    return null;
                }

                /** @var array<string, mixed> $item */
                $collection->attach($collectionType::hydrate($item));
            }

            return $collection;
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace kuaukutsu\dto;

use Closure;
use ReflectionClass;
use ReflectionException;
use TypeError;
use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\Strings\Inflector;

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
 */
final class Hydrator
{
    /**
     * Mapping
     *
     * @var array<string, string|Closure> Массив пересечения схем между насыщаемым объектов и данными.
     */
    private array $map;

    /**
     * @var string[] Массив свойств объекта которые были найдены в массиве данных.
     */
    private array $fields = [];

    /**
     * @var string Случайная строка, примесь
     */
    private string $hashStub = '619a799747d348fa1caf181a72b65d9f';

    /**
     * @var Inflector Для преобразования строки pascalCaseToId
     */
    private Inflector $inflector;

    /**
     * Hydrator constructor.
     *
     * @param string[]|array<string, string|Closure> $map Может быть:
     * - ассоциативным массивом (слева: свойство объекта; справа: путь до данных в массиве)
     * - плоским массивом, тогда считам что свойства объекта, есть и путь до данных в массиве
     */
    public function __construct(array $map)
    {
        $this->map = [];
        foreach ($map as $keyTo => $keyFrom) {
            if (is_int($keyTo)) {
                if (is_string($keyFrom) === false) {
                    throw new TypeError('Array item must be a string.');
                }

                $keyTo = $keyFrom;
            }

            $this->map[$keyTo] = $keyFrom;
        }

        $this->inflector = (new Inflector())->withoutIntl();
    }

    /**
     * @param array<string, mixed> $data Массив с данными
     * @param class-string $className Имя класса, на основе которого будет создан объект
     * @return DtoInterface
     * @throws ReflectionException
     * @template T of DtoInterface
     * @psalm-param class-string<T> $className
     */
    public function hydrate(array $data, string $className): DtoInterface
    {
        $reflection = new ReflectionClass($className);

        /** @var DtoInterface $object */
        $object = $reflection->newInstanceWithoutConstructor();
        $default = $reflection->getDefaultProperties();

        $this->hashStub = spl_object_hash($object);
        foreach ($this->map as $name => $propertyValue) {
            if ($reflection->hasProperty($name)) {
                $property = $reflection->getProperty($name);
                $property->setAccessible(true);
                $property->setValue($object, $this->getValue($name, $propertyValue, $data, $default[$name] ?? null));
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
     * @param string $name
     * @param Closure|string $value
     * @param array $data
     * @param mixed|null $default
     * @return mixed
     */
    private function getValue(string $name, $value, array $data, $default = null)
    {
        if ($value instanceof Closure) {
            $this->fields[] = $name;

            return $value($data);
        }

        /**
         * Фокус: если по обычному ключу в массиве данных нет значений,
         * то пробуем найти ключ в данных (изменить на snake_case и поискать ещё раз),
         * Если ключ найден - вернём значение, если нет, то вернём хэш заглушку (свойство не определено).
         * Это нужно для составления карты реально переданных свойств в data (fieldsUsedInMap).
         */
        $propertyValue = ArrayHelper::getValueByPath($data, $value, $this->hashStub);
        if ($propertyValue === $this->hashStub) {
            $propertyValue = ArrayHelper::getValueByPath(
                $data,
                $this->inflector->pascalCaseToId($value, '_'),
                $this->hashStub
            );
        }

        if ($propertyValue !== $this->hashStub) {
            $this->fields[] = $name;

            return $propertyValue;
        }

        return $default;
    }
}

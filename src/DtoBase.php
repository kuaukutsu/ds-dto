<?php

declare(strict_types=1);

namespace kuaukutsu\ds\dto;

use Closure;
use ReflectionClass;
use ReflectionProperty;
use kuaukutsu\ds\collection\Collection;

/**
 * Базовый класс для объекта DTO.
 * DTO простой класс для обмена данными между компонентами.
 * Не должно быть никакой бизнес логики.
 *
 * @psalm-immutable
 */
abstract class DtoBase implements DtoInterface
{
    /**
     * @var string[] Имена свойств которые участвуют в мапинге
     */
    private array $fieldsUsedInMap = [];

    /**
     * Создаёт объект DTO на основе данных из массива
     *
     * @param array<string, mixed> $data Данные которыми необходимо заполнить экземпляр объекта
     * @param string[]|array<string, string|Closure> $map По умолчанию будет генерироваться на основе полей DTO
     * Если не задано, то получаем из структуры объекта: public|protected свойства.
     *
     * @return static
     * @noinspection PhpDocMissingThrowsInspection
     */
    final public static function hydrate(array $data, array $map = []): static
    {
        if ($map === []) {
            $map = static::fields();
        }

        /**
         * @var static
         * @noinspection PhpUnhandledExceptionInspection
         */
        return (new Hydrator($map))->hydrate($data, static::class);
    }

    /**
     * Converts the object into an array.
     *
     * @param string[] $fields the fields that the output array should contain.
     * @return array<string, mixed> the array representation of the object
     */
    final public function toArray(array $fields = []): array
    {
        if ($fields === []) {
            $fields = $this->fieldsUsedInMap;
        } elseif ($fields === ['*']) {
            return $this->getPropertiesVars();
        } else {
            $structure = [];
            $properties = $this->getPropertiesVars();
            foreach ($fields as $key) {
                if (array_key_exists($key, $properties)) {
                    $structure[$key] = $properties[$key];
                }
            }

            return $structure;
        }

        return array_filter(
            get_object_vars($this),
            static fn(string $key): bool => in_array($key, $fields, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Converts the object into an array, uses a recursively return array representation of embedded objects.
     *
     * @param string[] $fields
     * @return array<string, mixed>
     */
    final public function toArrayRecursive(array $fields = []): array
    {
        $dtoToArray = $this->toArray($fields);
        foreach ($dtoToArray as $property => $value) {
            if ($value instanceof DtoInterface) {
                $dtoToArray[$property] = $value->toArrayRecursive();
                continue;
            }

            if ($value instanceof Collection) {
                $dtoToArray[$property] = $this->castTraversableToArray($value);
            }
        }

        return $dtoToArray;
    }

    /**
     * @return string[]|array<string, string|Closure>
     */
    protected static function fields(): array
    {
        return array_keys(get_class_vars(static::class));
    }

    /**
     * @return array<string, mixed>
     */
    private function getPropertiesVars(): array
    {
        $properties = (new ReflectionClass($this))
            ->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

        $structure = [];
        foreach ($properties as $property) {
            /** @psalm-suppress ImpureMethodCall */
            $structure[$property->name] = $property->getValue($this);
        }

        return $structure;
    }

    /**
     * @return array<array>
     */
    private function castTraversableToArray(iterable $traversable): array
    {
        $collection = [];
        foreach ($traversable as $item) {
            if ($item instanceof DtoInterface) {
                $collection[] = $item->toArrayRecursive();
                continue;
            }

            if ($item instanceof Collection) {
                $collection[] = $this->castTraversableToArray($item);
            }
        }

        return $collection;
    }
}

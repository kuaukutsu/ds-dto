<?php

declare(strict_types=1);

namespace kuaukutsu\dto;

use Closure;

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
     * @psalm-suppress MoreSpecificReturnType в 7.4 нет типа static
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    final public static function hydrate(array $data, array $map = []): DtoInterface
    {
        if ($map === []) {
            $map = static::fields();
        }

        /**
         * @psalm-suppress LessSpecificReturnStatement
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
        $isSortedFields = true;

        if ($fields === []) {
            $isSortedFields = false;
            $fields = $this->getFieldsUsedInMap();
        }

        if ($isSortedFields) {
            $list = [];
            $properties = get_object_vars($this);
            foreach ($fields as $key) {
                $list[$key] = $properties[$key] ?? null;
            }

            return $list;
        }

        return array_filter(
            get_object_vars($this),
            static function (string $key) use ($fields): bool {
                return in_array($key, $fields, true);
            },
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
     * @return string[] Имена свойств которые участвуют в мапинге.
     */
    protected function getFieldsUsedInMap(): array
    {
        return array_unique($this->fieldsUsedInMap);
    }
}

<?php

declare(strict_types=1);

namespace kuaukutsu\dto;

use Closure;

/**
 * DTO должны реализовывать публичные методы:
 * - из массива в объект DTO
 * - из объекта DTO в массив
 *
 * @psalm-immutable
 */
interface DtoInterface
{
    /**
     * Создаёт объект DTO на основе данных из массива
     *
     * @param array<string, mixed> $data Данные которыми необходимо заполнить экземпляр объекта
     * @param string[]|array<string, string|Closure> $map По умолчанию будет генерироваться на основе полей DTO
     * Если не задано, то получаем из структуры объекта: public|protected свойства.
     *
     * @return DtoInterface
     */
    public static function hydrate(array $data, array $map = []): DtoInterface;

    /**
     * Converts the object into an array.
     *
     * @param string[] $fields the fields that the output array should contain.
     * @return array<string, mixed> the array representation of the object
     */
    public function toArray(array $fields = []): array;

    /**
     * Converts the object into an array, uses a recursively return array representation of embedded objects.
     *
     * @param string[] $fields the fields that the output array should contain.
     * @return array<string, mixed> the array representation of the object
     */
    public function toArrayRecursive(array $fields = []): array;
}

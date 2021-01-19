<?php

declare(strict_types=1);

namespace kuaukutsu\dto;

/**
 * Interface DtoInterface
 *
 * DTO должны реализовывать публичные методы:
 * - из массива в объект DTO
 * - из объекта DTO в массив
 */
interface DtoInterface
{
    /**
     * Создаёт объект DTO на основе данных из массива
     *
     * @param array<string, mixed> $data данные
     * @param string[]|array<string, string> $map карта соответствия свойств объекта DTO данным в $data.
     * Если не задано, то получаем из структуры объекта: public|protected свойства.
     *
     * @return static
     */
    public static function hydrate(array $data, array $map = []): DtoInterface;

    /**
     * Converts the object into an array.
     *
     * @param string[] $fields the fields that the output array should contain.
     * @return array<string, mixed> the array representation of the object
     */
    public function toArray(array $fields = []): array;
}

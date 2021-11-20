# DTO

Disclaimer: вставлю свои 5 копеек, не претендую на истинность, но мысли озвучу (в самых общих чертах).

## Словарь используемых терминов:

- **Form** получение данных извне (Request: POST/GET/ARGS/Array), обработка (валидация, фильтрация), передача в
  сервисный слой (бизнес логика).
- **Model** какое-то объектное представления данных в системе (инфрастркутура).
- **Service** различные сервисы (UoW, UseCase), обработка бизнес логики.
- **DTO** транспорт, плюс схема данных (комментарии могут рассказывать о том, как и где используются данные). Есть
  возможность быстро организовать версионность массива данных.

## Схема для описания примера

В самом простом исполнении получается следующая схема:

```php
interface Form extends Dtoable
{
    /**
     * Конвертирует объект в массив.
     *
     * @param string[] $fields поля которые должны быть в исходном массиве
     * @return array<string, mixed>
     */
    public function toArray(array $fields = []): array;
    
    /**
     * Форма содержит данные, их нужно передать в сервис (например, Service). 
     *
     * @param class-string $dtoClassName
     */
    public function toDto(string $dtoClassName): DtoInterface;
}
```

```php
interface Model 
{
    public function fromDto(DtoInterface $dto): self;
}
```

```php
interface Service 
{
    public function save(DtoInterface $dto): bool;
}
```

## Пример **DTO**

Подходы могут быть разные, но лично мне не нравится передавать данные через конструктор, возможно с приходом php8 и
named arguments я поменяю точку зрения.

```php
/**
 * @psalm-immutable
 */
final class ModelDto extends BaseDto
{
    public int $id;

    public string $name;

    /**
     * nullable в данном случае говорит что значение при Инициализации объекта может быть незадано.
     */
    public ?array $props = [];
}
```

В **DTO** можно использовать `protected` свойства (плюс геттеры), чтобы не было желания заполнять их как-то кроме как
через `hydrate()`. Главное - **иммутабельность** и никакой логики, как только здесь появляется логика, это сразу
превращается в **Entity**, или быть может какой-то иной подвид **Value Object**.

Может возникнуть вопрос, зачем здесь DTO, ведь в сервисный слой можно передавать форму напрямую ($form или в виде
массива $form->toArray()), и так же получать из сервисного слоя напрямую модель. Суть в том, что Модель, как и Форма,
это реализация некоторой логики, и в приложении могут быть несколько компонент, которые реализуют логику
субъективно, по своему, с учётом требований БЛ, но сервисный слой для всех компонент один и тот же. Поэтому нужен
механизм, который позволит 3-м разным формам работать с одним методом, как например $service->save(DTO). А так же
потому что форма, как любой иной объект может менять своё состояние, и нет чётких гарантий неизменности данных.

```php
class Service 
{
    public function save(DtoInterface $dto): bool
    {
        $data = $dto->toArray();

        ...
    
        return true;
    }
}
```

## Docker

local

```shell
docker build -t kuaukutsu/dto:php .
docker run --init -it --rm -v "$(pwd):/app" -w /app kuaukutsu/dto:php sh
```

first run:

```shell
/app# apk add composer
/app# composer update
```

## Testing

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

local

```shell
docker run --init -it --rm -v "$(pwd):/app" -w /app kuaukutsu/dto:php ./vendor/bin/phpunit 
```

### Code Sniffer

local

```shell
docker run --init -it --rm -v "$(pwd):/app" -w /app kuaukutsu/dto:php ./vendor/bin/phpcs 
```

phpqa

```shell
docker run --init -it --rm -v "$(pwd):/app" -v "$(pwd)/phpqa/tmp:/tmp" -w /app jakzal/phpqa phpcs
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

local

```shell
docker run --init -it --rm -v "$(pwd):/app" -w /app kuaukutsu/dto:php ./vendor/bin/psalm 
```

phpqa

```shell
docker run --init -it --rm -v "$(pwd):/app" -v "$(pwd)/phpqa/tmp:/tmp" -w /app jakzal/phpqa psalm
```

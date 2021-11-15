# DTO

Вставлю свои 5 копеек, не претендую на истинность, но мысли озвучу (в самых общих чертах). 

**Form** получение данных извне (Request: POST/GET/ARGS/Array), обработка, передача в сервисный слой (бизнес логика).

**Model** получение данных изнутри (инфрастркутура, Repository).

**Service** различные сервисы (UoW, UseCase).

**DTO** транспорт, плюс схема данных (комментарии могут рассказывать о том, как и где используются данные). 
Так-то можно и с массивом работать, но хочется иметь типизированный код, его легче читать, легче обслуживать, 
несмотря на то что будет небольшой оверхед.

И в самом простом исполнении получается такая схема:

```php
interface DtoInterface 
{
    /**
     * Создаёт объект DTO на основе данных из массива.
     *
     * @param array<string, mixed> $data данные
     * @param string[]|array<string, string> $map карта соответствия свойств объекта DTO данным в $data.
     * Если не задано, то получаем из структуры объекта: public|protected свойства.
     *
     * @return static
     */
    public static function hydrate(array $data, array $map = []): DtoInterface;

    /**
     * Конвертирует объект в массив.
     *
     * @param string[] $fields поля которые должны быть в исходном массиве
     * @return array<string, mixed>
     */
    public function toArray(array $fields = []): array;
}
```

```php
interface Form 
{
    /**
     * Форма содержит данные, их нужно передать в сервис (например, Service). 
     *
     * @psalm-param class-string $dtoClassName
     */
    public function toDto(string $dtoClassName): DtoInterface;

    /**
     * Конвертирует объект в массив.
     *
     * @param string[] $fields поля которые должны быть в исходном массиве
     * @return array<string, mixed>
     */
    public function toArray(array $fields = []): array;
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

Пример **DTO**  
Подходы могут быть разные, но лично мне не нравится передавать данные через конструктор, 
возможно с приходом php8 и named arguments я поменяю точку зрения. 

```php
/**
 * @psalm-immutable
 */
final class ModelDto extends BaseDto
{
    protected int $id;

    protected string $name;

    protected ?array $props = [];

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getProps(): array
    {
        return $this->props ?? [];
    }
}
```

В DTO именно protected свойства, чтобы не было желания заполнять их как-то кроме как через hydrate(). 
Главное - иммутабельность и никакой логики, как только здесь появляется логика, это сразу превращается в Entity, 
или быть может какой-то подвид Value Object.

Но можно и более классический вариант, главное следить за тем чтобы сохранялась иммутабельность.

```php
/**
 * @psalm-immutable
 */
final class ClassicDto extends BaseDto
{
    public ?int $id = null;

    public ?string $name = null;
}
```

Может возникнуть вопрос, зачем здесь DTO, ведь в сервисный слой можно передавать форму напрямую ($form или в виде массива $form->toArray()), 
и так же получать из сервисного слоя напрямую модель. Суть в том, что Модель, как и Форма, это реализация некоторой логики,
у нас в приложении могут быть несколько компонент, которые реализуют логику субъективно, по своему, с учётом требований БЛ, 
но сервисный слой для всех компонент один и тот же. Поэтому нужен механизм, который позволит 3-м разным формам работать с одним методом, 
как например $service->save(DATA).

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

```php
class Form
{
    public function save(): bool
    {
        if ($this->validate()) {
            return $this->service->save($this->toDto(SimpleDto::class));
            // фактически тоже самое что и 
            // return $this->service->save(SimpleDto::hydrate($this->toArray()));
        }

        return false;
    }
}
```

## Docker

local
```shell
docker run --init -it --rm -v "$(pwd):/app" -w /app ghcr.io/hrmessenger/php:fpm sh
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
docker run --init -it --rm -v "$(pwd):/app" -w /app ghcr.io/hrmessenger/php:fpm ./vendor/bin/phpunit 
```

### Code Sniffer

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
docker run --init -it --rm -v "$(pwd):/app" -w /app ghcr.io/hrmessenger/php:fpm ./vendor/bin/psalm 
```

phpqa
```shell
docker run --init -it --rm -v "$(pwd):/app" -v "$(pwd)/phpqa/tmp:/tmp" -w /app jakzal/phpqa psalm
```

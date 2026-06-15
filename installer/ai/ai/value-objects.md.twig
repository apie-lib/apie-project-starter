# Apie Value Objects

Value Objects in Apie are how value objects are meant to be: immutable objects defined by their attributes rather than a unique identity. They are self-validating and ensure that the domain model remains in a consistent state.

## Core Concepts

All Value Objects in Apie should implement `Apie\Core\ValueObjects\Interfaces\ValueObjectInterface`.
A Value Object typically has:
- **Immutability**: Once created, its state cannot change.
- **Self-Validation**: Validation happens during instantiation (usually in the constructor or via traits).
- **Primitive Conversion**: Methods like `toNative()` to get the underlying value and a static `fromNative()` for creation.

## Simple Value Objects

Most Value Objects wrap a single primitive type (like a string or integer). Apie provides traits to simplify this.
There is a Apie\Core\ValueObjects\Utils class to convert values to specific primitives.

### String Value Objects
Use the `IsStringValueObject` trait for objects that wrap a string. You can add validation by creating a static validate function.
This function should not return anything, but should throw errors on invalid input. A convert method could be overwritten to do
some sanitization, for example trimming trailing spaces etc. This is still optional, so only use it if it is a requirement.

```php
<?php

namespace App\Apie\Example\ValueObjects;

use Apie\Core\ValueObjects\Interfaces\ValueObjectInterface;
use Apie\Core\ValueObjects\IsStringValueObject;

final readonly class OrderNumber implements ValueObjectInterface
{
    use IsStringValueObject;

    protected function convert(string $input): string
    {
        return trim($input);
    }

    public static function validate(string $input): void
    {
        if (!preg_match('/^ORD-\d{5}$/', $value)) {
            throw new \InvalidArgumentException('Order number must be in format ORD-12345');
        }
        $this->value = $value;
    }
}
```

### Using Regex Traits
Apie provides `IsStringWithRegexValueObject` to handle regex validation more declaratively.

```php
<?php

namespace App\Apie\Example\ValueObjects;

use Apie\Core\ValueObjects\Interfaces\ValueObjectInterface;
use Apie\Core\ValueObjects\IsStringWithRegexValueObject;

final readonly class ZipCode implements ValueObjectInterface
{
    use IsStringWithRegexValueObject;

    public static function getRegularExpression(): string
    {
        return '/^[0-9]{5}(-[0-9]{4})?$/';
    }
}
```

## Composite Value Objects

For objects with multiple properties that should be in a shared valid state, use the `CompositeValueObject` trait. This trait uses reflection to automatically handle `fromNative()` and `toNative()` for all non-internal properties. These classes can not be readonly as the CompositeValueObject does some caching on the reflection objects.

```php
<?php

namespace App\Apie\Example\ValueObjects;

use Apie\Core\ValueObjects\Interfaces\ValueObjectInterface;
use Apie\Core\ValueObjects\CompositeValueObject;
use Apie\CommonValueObjects\Texts\NonEmptyString;

final class Address implements ValueObjectInterface
{
    use CompositeValueObject;

    public function __construct(
        private NonEmptyString $street,
        private NonEmptyString $city,
        private ZipCode $zipCode
    ) {
    }
}
```

### Cross-Field Validation
If you need to validate the relationship between multiple fields in a composite vaue object, implement a `validateState()` method. The `CompositeValueObject` trait will call this method after populating the fields.

```php
<?php

namespace App\Apie\Example\ValueObjects;

use Apie\Core\ValueObjects\Interfaces\ValueObjectInterface;
use Apie\Core\ValueObjects\CompositeValueObject;

final readonly class DateRange implements ValueObjectInterface
{
    use CompositeValueObject;

    public function __construct(
        private \DateTimeImmutable $startDate,
        private \DateTimeImmutable $endDate
    ) {
        $this->validateState(); // this is only needed in the constructor
    }

    private function validateState(): void
    {
        if ($this->startDate > $this->endDate) {
            throw new \InvalidArgumentException('Start date cannot be after end date');
        }
    }
}
```

## Built-in Value Objects

Apie comes with many pre-built Value Objects, for example in the `Apie\CommonValueObjects` namespace:

- **Identifiers**: `UuidV4`, `KebabCaseSlug`, etc.
- **Text**: `Email`, `Password`, `NonEmptyString`, `SafeHtml`.
- **Numbers**: `PositiveInteger`, `NegativeInteger`.

But there are more options. You could get a list of found value objects by installing apie/apie-common-plugin as a composer plugin.
After running composer install you can get all found value objects with static method Apie\ApieCommonPlugin\AvailableApieObjectProvider::getAvailableValueObjects()

## Integration with Domain Objects

Value Objects are used as property types in Domain Objects. Apie's automatically handles the conversion between the API's JSON representation and the Value Object.

```php
<?php

namespace App\Apie\Example\Resources;

use Apie\Core\Entities\EntityInterface;
use App\Apie\Example\ValueObjects\OrderNumber;
use App\Apie\Example\Identifiers\ExampleIdentifier;

class Order implements EntityInterface
{
    private ExampleIdentifier $id;

    public function __construct(
        private OrderNumber $orderNumber,
        private float $amount
    ) {
        $this->id = ExampleIdentifier::createRandom();
    }
    
    public function getId(): ExampleIdentifier
    {
        return $this->id;
    }
}
```

## Tips for AI Agents

1. **Always use `readonly`**: Value Objects must be immutable.
2. **Prefer Composition**: Use existing Value Objects (like `NonEmptyString`) as building blocks for your own.
3. **Self-Contained**: Ensure all validation logic is inside the Value Object, not in the service or entity using it.
4. **Namespace**: Place custom Value Objects in `App\Apie\{BoundedContext}\ValueObjects`.
5. **Avoid primitive obsession**: Prefer value objects over string, int, float, bool etc.

# Apie domain objects
A common Apie domain object looks like this:

```php
<?php

namespace App\Apie\Example\Resources;

use Apie\Core\Entities\EntityInterface;
use App\Apie\Example\Dtos\DtoExample;
use App\Apie\Example\Identifiers\ExampleIdentifier;

class Example implements EntityInterface
{
    private ExampleIdentifier $id;

    public DtoExample $dtoExample;

    public function __construct(private string $requiredString)
    {
        $this->id = ExampleIdentifier::createRandom(); // @phpstan-ignore-line
    }
    public function getId(): ExampleIdentifier
    {
        return $this->id;
    }

    public function getRequiredString(): string
    {
        return $this->requiredString;
    }
}
```
In this example we have an Example domain object that generates a random id and has a requiredString property. The requiredString property has typehint string so it can contain any string including the empty string. Since it is in the constructor it is required on creating the domain object.
There is also a public property $dtoExample. It is not part of the constructor, so this field is not required. Since it is a public property it can be modified. For example in the Rest API you would now have a ```PATCH /Example/{id}``` operation you can use to modify dtoExample property. Since requiredString is not a public property and not a setter, it can not be modified with PATCH.

## Adding a required property
To add a required property to our Example entity we need to add a new promoted property:

```php
<?php

namespace App\Apie\Example\Resources;

use Apie\Core\Entities\EntityInterface;
use App\Apie\Example\Dtos\DtoExample;
use App\Apie\Example\Identifiers\ExampleIdentifier;

class Example implements EntityInterface
{
    private ExampleIdentifier $id;

    public DtoExample $dtoExample;

    public function __construct(private string $requiredString, private int $rating)
    {
        $this->id = ExampleId::createRandom(); // @phpstan-ignore-line
    }
    public function getId(): ExampleIdentifier
    {
        return $this->id;
    }

    public function getRequiredString(): string
    {
        return $this->requiredString;
    }

    public function getRating(): int
    {
        return $this->rating;
    }
}
```
If we want to be able to change rating we could add a setRating method or make $rating a public property. If we use setRating we could even
add validation:
```php
<?php

namespace App\Apie\Example\Resources;

use Apie\Core\Entities\EntityInterface;
use App\Apie\Example\Dtos\DtoExample;
use App\Apie\Example\Identifiers\ExampleIdentifier;

class Example implements EntityInterface
{
    private ExampleIdentifier $id;

    private int $rating;

    public DtoExample $dtoExample;

    public function __construct(private string $requiredString, int $rating)
    {
        $this->id = ExampleId::createRandom(); // @phpstan-ignore-line
        $this->setRating($rating);
    }
    public function getId(): ExampleIdentifier
    {
        return $this->id;
    }

    public function setRating(int $rating): void
    {
        if ($rating < 0 || $rating > 10) {
            throw new \InvalidArgumentException('Rating should be between 0 and 10');
        }
        $this->rating = $rating;
    }

    public function getRequiredString(): string
    {
        return $this->requiredString;
    }

    public function getRating(): int
    {
        return $this->rating;
    }
}
```
In case we already have this running in production without rating it is possible that existing records in the database have no $rating property yet. To avoid an error we would need to make the rating property nullable. We could add logic in the getter how to handle it or return a nullable integer here in case there is no fallback.

```php
<?php

namespace App\Apie\Example\Resources;

use Apie\Core\Entities\EntityInterface;
use App\Apie\Example\Dtos\DtoExample;
use App\Apie\Example\Identifiers\ExampleIdentifier;

class Example implements EntityInterface
{
    private ExampleIdentifier $id;

    private ?int $rating = null;

    public DtoExample $dtoExample;

    public function __construct(private string $requiredString, int $rating)
    {
        $this->id = ExampleId::createRandom(); // @phpstan-ignore-line
        $this->setRating($rating);
    }
    public function getId(): ExampleIdentifier
    {
        return $this->id;
    }

    public function setRating(int $rating): void
    {
        if ($rating < 0 || $rating > 10) {
            throw new \InvalidArgumentException('Rating should be between 0 and 10');
        }
        $this->rating = $rating;
    }

    public function getRequiredString(): string
    {
        return $this->requiredString;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }
}
```

## Apply operation on domain objects
Apie follows non-anemic domain objects, so you should not always think in CRUD. If you have a user, you can add methods like activate and deactivate:
```php
<?php

namespace App\Apie\Example\Resources;

use Apie\Core\ApieLib;
use Apie\Core\Entities\EntityInterface;
use App\Apie\Example\Dtos\DtoExample;
use App\Apie\Example\Identifiers\ExampleIdentifier;

class User implements EntityInterface
{
    private UserStatus $status = UserStatus::Inactive;
    private ?DateTimeImmutable $activatedAt = null;
    private ?DateTimeImmutable $deactivatedAt = null;
    private ?string $reason = null;
// rest of class not shown for clarity

    public function activate()
    {
        $this->status->ensureInactive();
        $this->status = UserStatus::Active;
        $this->deactivatedAt = null;
        // ApieLib::getClock() gives a PSR-20 clock object for testing time-related activities
        $this->activatedAt = ApieLib::getClock()->now();
    }

    public function deactivate(?string $reason)
    {
        $this->status->ensureActive();
        $this->status = UserStatus::Inactive;
        $this->activatedAt = null;
        // ApieLib::getClock() gives a PSR-20 clock object for testing time-related activities
        $this->deactivatedAt = ApieLib::getClock()->now();
        $this->reason = $reason;
    }
}
```
## Creating an authentication endpoint
Adds an action with a method 'verifyAuthentication' and return a persisted entity to be logged in. The arguments of the function are used for validation, so you can easily make your own authentication combination: with or without 2FA, etc.

```php
namespace App\Apie\Example\Resources;

use Apie\Core\Attributes\Description;
use Apie\Core\Attributes\NotLoggedIn;
use Apie\Core\Attributes\RuntimeCheck;
use Apie\Core\Datalayers\ApieDatalayer;

class DevelopLoginAction
{
    #[RuntimeCheck(new NotLoggedIn())]
    #[Description('dev only login to be logged in as the first user in the database')]
    public function verifyAuthentication(#[Context] ApieDatalayer $apieDatalayer): User
    {
        foreach ($apieDatalayer->all(User::class, new BoundedContextId('example')) as $user) {
            return $user;
        }

        throw new \LogicException('There is no user to login');
    }
}
```

## Authorization checks
Authorization checks are not part of a domain object, but since we have no controller we do this with attributes.
There are 2 default attributes: RuntimeCheck and StaticCheck. With a RuntimeCheck you can add for example the restriction you need to be logged in. With a StaticCheck you can define if Apie should generate routes for it (for example you only want the option in the console, but not as an Api Call).

If you put the attribute on the class it will apply to all operations.
If you put the attritute on a setter it will apply to setting the value.
If you put the attribute on the constructor it will apply to creating the object.
If you put the attrbute on a method action of the domain object it will do the check when calling this method.
If there are setters or public properties but you are not allowed to set any of these values you will get an access denied operation.

For example this is a way I can make admin users only from the console. The API url is not even being generated if StaticCheck fails.
Make sure you never use StaticCheck to see if a user is logged in!

```php
<?php
namespace App\Apie\Example\Actions;

use Apie\CommonValueObjects\Email;
use Apie\Core\BoundedContext\BoundedContextId;
use Apie\Core\Datalayers\ApieDatalayer;
use Apie\Core\Enums\ConsoleCommand;

class CreateAdminUser
{
    #[StaticCheck(new Requires(ConsoleCommand::CONSOLE_COMMAND))]
    public function __invoke(
        #[Context] ApieDatalayer $apieDatalayer,
        #[Context] BoundedContextId $boundedContextId,
        Email $email
    ): User {
        return $apieDatalayer->persistNew(new User($email, Role::Admin), $boundedContextId);
    }
}
```

Most Attributes you can use can be found in Apie\Core\Attributes.
How it works: Internally Apie works with an ApieContext object which is a bit like a configuration/service container blob. Requires means that there is a definition with the key ConsoleCommand::CONSOLE_COMMAND. The most common key used in permissions
is 'authenticated' or ContextConstants::AUTHENTICATED_USER. Many Attributes are written like business rule classes, for example
there is a HasRole attribute which assumes the current logged in user implements HasRoleInterface and has the specified role.
And for example Requires('authenticated') is the same as using IsLoggedIn()

### Remove domain objects
By default you can not delete records. To add it we add a RemovalCheck attribute. It needs a RuntimeCheck or a StaticCheck as argument. 

```php
<?php

namespace App\Apie\Example\Resources;

use Apie\Core\Attributes\RemovalCheck;
use Apie\Core\Attributes\Requires;
use Apie\Core\Attributes\RuntimeCheck;
use Apie\Core\Attributes\StaticCheck;
use Apie\Core\ContextConstants;
use Apie\Core\Entities\EntityInterface;
use App\Apie\Example\Dtos\DtoExample;
use App\Apie\Example\Identifiers\ExampleIdentifier;

#[RuntimeCheck(new Requires(ContextConstants::AUTHENTICATED_USER))]
#[RemovalCheck(new StaticCheck())]
class Example implements EntityInterface
{
    private ExampleIdentifier $id;

    public DtoExample $dtoExample;

    public function __construct(private string $requiredString)
    {
        $this->id = ExampleIdentifier::createRandom(); // @phpstan-ignore-line
    }
    public function getId(): ExampleIdentifier
    {
        return $this->id;
    }

    public function getRequiredString(): string
    {
        return $this->requiredString;
    }
}
```
### Restrictions on which records of a domain object you can see.
For example we could add a restriction that you can only see domain objects of the same company of the logged in user.
Apie does some magic here to make sure the GET /entity/{id} and GET /entity are consistent and fast in behaviour:

Add to the domain object this interface: Apie\Core\Permissions\RequiresPermissionsInterface. 

```php
<?php

namespace App\Apie\Example\Resources;

use Apie\Core\Attributes\RemovalCheck;
use Apie\Core\Attributes\Requires;
use Apie\Core\Attributes\RuntimeCheck;
use Apie\Core\Attributes\StaticCheck;
use Apie\Core\ContextConstants;
use Apie\Core\Entities\EntityInterface;
use Apie\Core\Lists\PermissionList;
use Apie\Core\Permissions\RequiresPermissionsInterface;
use App\Apie\Example\Dtos\DtoExample;
use App\Apie\Example\Identifiers\ExampleIdentifier;
use App\Apie\Example\Identifiers\CompanyIdentifier;

#[RuntimeCheck(new Requires(ContextConstants::AUTHENTICATED_USER))]
#[RemovalCheck(new StaticCheck())]
class Example implements EntityInterface, RequiresPermissionsInterface
{
    private ExampleIdentifier $id;

    public DtoExample $dtoExample;

    public function __construct(private CompanyIdentifier $company)
    {
        $this->id = ExampleIdentifier::createRandom(); // @phpstan-ignore-line
    }
    public function getId(): ExampleIdentifier
    {
        return $this->id;
    }

    public function getCompany(): CompanyIdentifier
    {
        return $this->company;
    }

    public function getRequiredPermissions(): PermissionList
    {
        return new PermissionList(['company:' . $this->company, 'admin']);
    }
}
```
And to the user add the Apie\Core\Permissions\PermissionInterface to the user domain object:

```php
<?php

namespace App\Apie\Example\Resources;

use Apie\Core\Attributes\RemovalCheck;
use Apie\Core\Attributes\Requires;
use Apie\Core\Attributes\RuntimeCheck;
use Apie\Core\Attributes\StaticCheck;
use Apie\Core\ContextConstants;
use Apie\Core\Entities\EntityInterface;
use Apie\Core\Lists\PermissionList;
use Apie\Core\Permissions\RequiresPermissionsInterface;
use App\Apie\Example\Dtos\DtoExample;
use App\Apie\Example\Identifiers\UserIdentifier;
use App\Apie\Example\Identifiers\CompanyIdentifier;

#[RuntimeCheck(new Requires(ContextConstants::AUTHENTICATED_USER))]
class User implements EntityInterface, RequiresPermissionsInterface
{
    private UserIdentifier $id;

    public function __construct(private ?CompanyIdentifier $company, private UserRole $userRole)
    {
        $this->id = UserIdentifier::createRandom(); // @phpstan-ignore-line
    }
    public function getId(): UserIdentifier
    {
        return $this->id;
    }

    public function getCompany(): ?CompanyIdentifier
    {
        return $this->company;
    }

    public function getPermissionIdentifiers(): PermissionList
    {
        if ($this->userRole === UserRole::Admin) {
            return new PermissionList(['admin']);
        }
        if ($this->company) {
            return new PermissionList(['company:' . $this->company]);
        }

        return new PermissionList();
    }
}
```
If the domain object and the user object has any overlap they can see each other.

### Policy checks
Apie supports Policy checks similar how Laravel does them. For example to add a policy for a domain object App\Apie\Example\Resources\DomainObject, you will create an App\Apie\Examples\Policies\DomainObjectPolicy class.
You still need to add the attribute that a policy should be followed:
```php
<?php

namespace App\Apie\Example\Resources;

use Apie\Core\ApieLib;
use Apie\Core\Attributes\Policy;
use Apie\Core\Attributes\RuntimeCheck;
use Apie\Core\Entities\EntityInterface;
use App\Apie\Example\Dtos\DtoExample;
use App\Apie\Example\Identifiers\ExampleIdentifier;

class User implements EntityInterface
{
    private UserStatus $status = UserStatus::Inactive;
    private ?DateTimeImmutable $activatedAt = null;
    private ?DateTimeImmutable $deactivatedAt = null;
    private ?string $reason = null;
// rest of class not shown for clarity

    #[RuntimeCheck(new Policy('canActivate'))]
    public function activate()
    {
        $this->status->ensureInactive();
        $this->status = UserStatus::Active;
        $this->deactivatedAt = null;
        // ApieLib::getClock() gives a PSR-20 clock object for testing time-related activities
        $this->activatedAt = ApieLib::getClock()->now();
    }

    #[RuntimeCheck(new Policy('canDeactivate'))]
    public function deactivate(?string $reason)
    {
        $this->status->ensureActive();
        $this->status = UserStatus::Inactive;
        $this->activatedAt = null;
        // ApieLib::getClock() gives a PSR-20 clock object for testing time-related activities
        $this->deactivatedAt = ApieLib::getClock()->now();
        $this->reason = $reason;
    }
}
```

And add this policy class:
```php
<?php

namespace App\Apie\Example\Policies;

use Apie\Core\ApieLib;
use Apie\Core\Attributes\Policy;
use Apie\Core\Attributes\RuntimeCheck;
use Apie\Core\Entities\EntityInterface;
use App\Apie\Example\Resources\User;

class UserPolicy implements EntityInterface
{
    public function canActivate(User $user): bool
    {
        return $user->getStatus()->isActive();
    }

    public function canDeactivate(User $user): bool
    {
        return !$user->getStatus()->isActive();
    }
}
```
All policy method arguments are mapped to keys in the ApieContext. You can also specify the key with the #[Context] attribute as
you can also do in domain objects.


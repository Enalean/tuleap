# `Tuleap\Option`

Tuleap's Option namespace has the goal of bringing a PHP implementation of the `Maybe`/`Option` type present in functional
programming languages. See [ADR-0022: Option][0] for more context.

## Usage

Functions returning a value can use `Option::fromValue(...)` and `Option::nothing(...)`.

Example:

```php
/**
 * @psalm-return \Tuleap\Option\Option<\PFUser>
 */
function getSuperUser(\PFUser $user): \Tuleap\Option\Option
{
    if ($user->isSuperUser()) {
        return \Tuleap\Option\Option::fromValue($user);
    }
    return \Tuleap\Option\Option::nothing(\PFUser::class);
}
```

If you work on a scalar/array value you can use `::nothing()` this way:

```php
\Tuleap\Option\Option::nothing(\Psl\Type\string()); // \Tuleap\Option\Option<string>
```

When working with existing code that returns `null|<ValueType>`, you can use `Option::fromNullable()`:

Example:

```php
function returnsANullable(): ?\PFUser
{
}

$option = Option::fromNullable($this->returnsANullable()); // \Tuleap\Option\Option<\PFUser>
// Option will be Nothing if the function returns null
```

You can then use the resulting value using `::apply()`:

```php
$option = getSuperUser($current_user);
$option->apply(function(\PFUser $user): void {
    // $current_user is a superuser, do something with it
});
```

You can transform the "inner type" (and do nothing when it is `nothing`) using `::map()`:

```php
class DerivedState {
    public function __construct(public readonly string $derived_value)
    {
    }
};

$option = getSuperUser($current_user);
$mapped_option = $option->map(static function(\PFUser $user): DerivedState {
    return new DerivedState($user->getUserName());
});
// if option was `nothing`, mapped_option is still `nothing`.
// if option has a value, mapped_option is a new `Option<DerivedState>`.
```

If your optional value is needed to build another optional value, you can chain the two with `::andThen()`:

```php
$option = getSuperUser($current_user);
$other_option = $option->andThen(fn(\PFUser $user): Option => getUserPreference($user));
// $other_option will hold a `UserPreference` or Nothing, depending on the return of the function `getUserPreference`
```

If you need to convert `nothing` into an optional value, you can chain with `::orElse()`:
```php
$option = getUser($name)->orElse(fn() => ceateUser($name));
// $option will hold the User returned by `getUser` unless
// nothing is returned then it will hold the User returned
// by `createUser`
```

If you need to convert the optional value to a `Result (Ok|Err)`, you can do so with `::okOr()`:

```php
$option = getSuperUser($current_user);
$result = $option->okOr(\Tuleap\NeverThrow\Result::err(\Tuleap\NeverThrow\Fault::fromMessage('Current user is not a super user')));
// If $option has a value, it will be wrapped in a `\Tuleap\NeverThrow\Ok`.
// If it's `nothing`, it will return the `\Tuleap\NeverThrow\Err` passed as parameter.
```

At the end of a processing pipeline, you might want to retrieve the unwrapped value with `::mapOr()`:

```php
$option = getSuperUser($current_user);
$option->mapOr(
    fn (\PFUser $user): UserPresenter => new SuperUserPresenter($user),
    new NotSuperUserPresenter()
);
```

If you need a separate treatment when the value is set or not, you can use `::match()`:

```php
$option = getSuperUser($current_user);
$option->match(
    function(\PFUser $user): void {
        // $current_user is a superuser, do something with it
    },
    function (): void {
        // $current_user is NOT a superuser, do something else
    }
);
```

In unit tests when the inner value is a primitive or an array, and you want to run assertions, use `::unwrapOr()`:

```php
/**
 * @return Option<string>
 */
function getOption(): Option;

$option = getOption();
self::assertSame('a string value', $option->unwrapOr(null));
```

If you need to collect the actual values of some optional values:

```php
$some_optional_values = [
    \Tuleap\Option\Option::fromValue("a"),
    \Tuleap\Option\Option::fromValue("b"),
    \Tuleap\Option\Option::nothing(\Psl\Type\string()),
];

$values = \Tuleap\Option\Options::collect($some_optional_values); // ["a", "b"]
```

## Links

* [ADR-0022: Option][0]

[0]: ../../../docs/decisions/0022-option.md

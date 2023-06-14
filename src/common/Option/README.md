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

You can then use the resulting value using `::apply()`:
```php
$option = getSuperUser($current_user);
$option->apply(function(\PFUser $user): void {
    // $current_user is a superuser, do something with it
});
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

At the end of a processing pipeline, you might want to retrieve the unwrapped value with `::mapOr()`:
```php
$option = getSuperUser($current_user);
$option->mapOr(
    fn (\PFUser $user): Ok => \Tuleap\NeverThrow\Result::ok($user),
    \Tuleap\NeverThrow\Result::err(\Tuleap\NeverThrow\Fault::fromMessage('Current user is not a super user')),
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

[0]: ../../../adr/0022-option.md

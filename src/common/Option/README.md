# `Tuleap\Option`

Tuleap's Option namespace has for goal to bring a PHP implementation of the `Maybe`/`Option` type present in functional
programming languages. See [Haskell's Maybe](https://wiki.haskell.org/Maybe) or
[Rust's Option](https://doc.rust-lang.org/std/option/enum.Option.html) for examples.

## Goals

* Bring better clarity to the fact some value may or may not be present
* Avoid using `null` to indicate a value might not be present. `null` can be a valid value or have a different meaning
  than "missing" (e.g. it can be a "to be removed")
* Avoid conditional logic in order to reduce testing efforts

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
$value = getSuperUser($current_user);
$value->apply(function(\PFUser $user): void {
    // $current_user is a superuser, do something with it
});
```

At the end of a processing pipeline you might want to retrieve the unwrapped value with `::mapOr()`:
```php
$value = getSuperUser($current_user);
$value->mapOr(
    fn (\PFUser $user): Ok => \Tuleap\NeverThrow\Result::ok($user),
    \Tuleap\NeverThrow\Result::err(\Tuleap\NeverThrow\Fault::fromMessage('Current user is not a super user')),
);
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

## Additional notes

* An `Option` type exists in `azjezz/psl`, we do not use it for the following reasons:
  * creating a `None` value with `Psl\Option\none()` gives `Option<never>` so you need to "force" the proper type with
    an annotation `@var`
  * our `Option` implementation predates the introduction of `azjezz/psl` in the codebase
* Java developers: our `Option<T>` is different to your `Optional<T>` since PHP has a decent handling of `null`
  values. `Optional<T>` means `T` or `null`. `Option<T>` means `T` or the absence of value.
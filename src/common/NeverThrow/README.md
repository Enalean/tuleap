# `Tuleap\NeverThrow`

Tuleap's NeverThrow namespace can be split into two parts: `Tuleap\NeverThrow\Fault` and the rest of the namespace.

## Fault

A better base type for error handling.

### Usage

There are three ways to create a `Fault`:

#### `Fault::fromMessage(string $message): Fault`

`fromMessage` returns a new Fault with the supplied message. It also records the stack trace at the point it was called.

Parameters:
- `string $message`: A message to explain what happened. This message could appear in log files or on-screen.

Returns: `Fault`

Example:
```php
$fault = Fault::fromMessage("User does not have permission");
```

#### `Fault::fromThrowable(\Throwable $throwable): Fault`

`fromThrowable` wraps an existing Throwable and returns a new Fault with its message and stack trace. It preserves both the message and the stack trace from `$throwable`.

Parameters:
- `\Throwable $throwable`: Wrapped throwable

Returns: `Fault`

Example:
```php
$fault = Fault::fromThrowable($this->functionThrowingAnException());
```

#### `Fault::fromThrowableWithMessage(\Throwable $throwable, string $message): Fault`

`fromThrowableWithMessage` wraps an existing Throwable and returns a new Fault with the supplied message. It discards the message from `$throwable`. It preserves the stack trace from `$throwable`.

Parameters:
- `\Throwable $throwable`: Wrapped throwable
- `string $message`: A message to explain what happened. This message could appear in log files or on-screen.

Returns: `Fault`

Example:
```php
$fault = Fault::fromThrowableWithMessage(
    $this->functionThrowingAnException(),
    "Could not retrieve data from storage"
);
```

#### `$fault_instance->__toString(): string`

`__toString()` allows casting the Fault to `string`.

Example:
```php
$fault = Fault::fromMessage("User does not have permission");
$this->logger->error((string) $fault); // logs "User does not have permission"
```

#### `$fault_instance->getStackTraceAsString(): string`

`getStackTraceAsString()` allows to print a stack trace, like a `\Throwable`. Useful for debugging.

Example:
```php
$fault = Fault::fromThrowable($this->functionThrowingAnException());
var_dump($fault->getStackTraceAsString());
```

### Specialized `Fault`

Faults can be extended to help distinguish them in error-handling. To do so, create a sub-class and use `instanceof`:

Examples:
```php
/** @psalm-immutable */
final class PermissionFault extends Tuleap\NeverThrow\Fault
{
    public function __construct() {
        parent::__construct("User does not have permission");
    }
}
```
```php
// In Fault handling code
if ($fault instanceof PermissionFault) {
    // ignore specifically Permission Denied error
} else {
    $this->logger->error((string) $fault);
}
```

## NeverThrow

Easier chaining of operations that can have an error. It lets you group error-handling in one place for many operations.

### Usage

#### `Result::ok(mixed $value): Ok`

`ok` creates a new Ok variant of Result wrapping `$value`.

Parameters:
- `mixed $value`: The wrapped value. Can be anything.

Returns: `Ok`

Example:
```php
$result = Result::ok("A string value");
```

#### `Result::err(mixed $error): Err`

`err` creates a new Err variant of Result wrapping `$error`.

Parameters:
- `mixed $error`: The wrapped error. Can be anything, but should be a Fault.

Returns: `Err`

Example:
```php
$result = Result::err(Fault::fromMessage("An error occurred"));
```

#### `Result::isOk(Ok|Err $result): bool`

`isOk` returns true if $result is an `Ok` variant. It lets you safely access the wrapped property of the `Ok` or the `Err` you passed.

Parameters:
- `Ok|Err $result`: The Result to detect.

Returns: `bool`

Example:
```php
if (Result::isOk($result)) {
    // If it's an Ok, you can access its value
    $this->logger->debug($result->value);
} else {
    // If it's an Err, you can access its error
    $this->logger->error((string) $result->error);
}
```

#### `Result::isErr(Ok|Err $result): bool`

`isErr` returns true if $result is an `Err` variant. It is the opposite operation to `Result::isOk()`. It lets you safely access the wrapped property of the `Ok` or the `Err` you passed.

Parameters:
- `Ok|Err $result`: The Result to detect.

Returns: `bool`

Example:
```php
if (Result::err($result)) {
    // If it's an Err, you can access its error
    $this->logger->error((string) $result->error);
} else {
    // If it's an Ok, you can access its value
    $this->logger->debug($result->value);
}
```

#### `$result_instance->andThen(callable $fn): Ok|Err`

`andThen` applies `$fn` to an `Ok` value, leaving `Err` untouched. It is useful when you need to do a subsequent computation using the inner `Ok` value, but that computation might fail. It allows you to chain calls that could return an error.

If called on an `Ok`, it returns the result of calling `$fn`. `$fn` must return a new `Ok|Err`. It can change the type of both the "inner" `Ok` value and the "inner" `Err` value.

If called on an `Err`, it returns the same `Err`.

It can change the variant of the result: you can go from an `Ok` to an `Err` if `$fn` returns an `Err` variant.

Additionally, andThen can be used to flatten a nested `Ok<Ok<ValueType> | Err<ErrorType>> | Err<OtherErrorType>` into a `Ok<ValueType> | Err<ErrorType>`.

Parameters:
- `$fn`: `function(mixed $value): Ok|Err`. A function returning a new `Ok|Err`. It will receive a single argument: the "inner" Ok value wrapped by `$result_instance`.

Returns: `Ok|Err`. It returns what is returned by `$fn`.

Example:
```php
private function aFunctionThatMightFail(): Ok|Err
{
    return Result::ok("Inner value");
}

private function anotherFunctionThatMightFail(string $inner_value): Ok|Err
{
    return $this->makeADistantCallThatCouldFail(
        "https://example.com",
        $inner_value
    );
}

public function chainTogether(): Ok|Err
{
    $second_result = $this->aFunctionThatMightFail()
        ->andThen([$this, 'anotherFunctionThatMightFail']);

    return $second_result;
}
```

```php
// Flatten nested Ok result
// $result holds a Ok<Ok<ValueType> | Err<ErrorType>> | Err<OtherErrorType>
$flattened = $result->andThen(static fn($inner_result) => $inner_result);
// $flattened holds a Ok<ValueType> | Err<ErrorType>
```

#### `$result_instance->orElse(callable $fn): Ok|Err`

`orElse` applies `$fn` to an `Err` value, leaving `Ok` untouched. It is useful when you want to recover from an error and do a computation using the inner `Err` value, but that computation might fail again.

If called on an `Ok`, it returns the same `Ok`.

If called on an `Err`, it returns the result of calling `$fn`. `$fn` must return a new `Ok|Err`. It can change the type of both the "inner" `Ok` value and the "inner" `Err` value.

Additionally, `orElse` can be used to flatten a nested `Ok<OtherValueType> | Err<Ok<ValueType> | Err<ErrorType>>` into a `Ok<ValueType> | Err<ErrorType>`.

Parameters:
- `$fn`: `function(mixed $error): Ok|Err`. A function returning a new `Ok|Err`. It will receive a single argument: the "inner" error wrapped by `$result_instance`.

Returns: `Ok|Err`. It returns what is returned by `$fn`.

Example:
```php
private function aFunctionThatMightFail(): Ok|Err
{
    return Result::err(Fault::fromMessage("Ooops"));
}

private function recoverFromError(Fault $inner_value): Ok|Err
{
    return $this->storeErrorInDatabase((string) $inner_value);
}

public function chainTogether(): Ok|Err
{
    $second_result = $this->aFunctionThatMightFail()
        ->orElse([$this, 'recoverFromError']);

    return $second_result;
}
```

```php
// Flatten nested Err result
// $result holds a Ok<OtherValueType> | Err<Ok<ValueType> | Err<ErrorType>>
$flattened = $result->orElse(static fn($inner_result) => $inner_result);
// $flattened holds a Ok<ValueType> | Err<ErrorType>
```

#### `$result_instance->match(callable $ok_fn, callable $err_fn): mixed`

`match` applies `$ok_fn` on an `Ok` value, or applies `$err_fn` to an `Err` value. Both callbacks _must_ have the same return type.

`match` is typically called at the end of a `Ok|Err` chain of operations, to deal with both the `Ok` and `Err` cases.

You don't need to return another `Ok|Err` (you can return `void`) but you can if you want to.

Parameters:
- `callable $ok_fn`: A function that will receive a single argument: the inner Ok value wrapped by `$result_instance`. It must have the same return type as `$err_fn`.
- `callable $err_fn`: A function that will receive a single argument: the inner error wrapped by `$result_instance`. It must have the same return type as `$ok_fn`.

Returns: `mixed`. It returns what is returned by `$ok_fn` or `$err_fn`.

Example:
```php
private function handleResult(): Presenter
{
    return $this->aFunctionThatMightFail()
        ->match(
            static fn($value) => Presenter::fromSuccess($value),
            static fn($fault) => Presenter::fromFault($fault)
        );
}
```

#### `$result_instance->map(callable $fn): Ok[Err`

`map` applies `$fn` to an `Ok` value, leaving `Err` untouched.

If called on an `Ok`, it returns a new `Ok` holding the result of calling `$fn`. It can change the type of the "inner" `Ok` value.

If called on an `Err`, it returns the same `Err`.

It maps from an `Ok<TValue> | Err<TError>` to an `Ok<TNewValue> | Err<TError>`.

`$fn` should _not_ return a new `Ok|Err`. It works on the "inner" type.

Parameters:
- `callable $fn`: A function that will receive a single argument: the inner Ok value wrapped by `$result_instance`. It can return any type, which will become the new inner Ok value.

Returns: `Ok|Err`. It returns a new `Ok|Err` wrapping what is returned by `$fn`.

Example:
```php
/** @returns Ok<AnObject> | Err<Fault> */
private function aFunctionThatMightFail(): Ok|Err
{
    return Result::ok(new AnObject());
}

/** @returns Ok<JSONRepresentation> | Err<Fault> */
private function modifyInnerValue(): Ok|Err
{
    return $this->aFunctionThatMightFail()
        ->map(static fn($value) => JSONRepresentation::fromValue($value));
}
```

#### `$result_instance->mapErr(callable $fn): Ok[Err`

`mapErr` applies `$fn` to an `Err` value, leaving `Ok` untouched.

If called on an `Ok`, it returns the same `Ok`.

If called on an `Err`, it returns a new `Err` holding the result of calling `$fn`. It can change the type of the "inner" `Err` value.

It maps from an `Ok<TValue> | Err<TError>` to an `Ok<TValue> | Err<TNewError>`.

`$fn` should _not_ return a new `Ok|Err`. It works on the "inner" type.

Parameters:
- `callable $fn`: A function that will receive a single argument: the inner error value wrapped by `$result_instance`. It can return any type, which will become the new inner error value.

Returns: `Ok|Err`. It returns a new `Ok|Err` wrapping what is returned by `$fn`.

Example:
```php
/** @returns Ok<AnObject> | Err<Fault> */
private function aFunctionThatMightFail(): Ok|Err
{
    return Result::err(Fault::fromMessage("Ooops"));
}

/**
 * @returns Ok<AnObject> | Err<SpecializedFault>
 */
private function modifyInnerError(): Ok|Err
{
    return $this->aFunctionThatMightFail()
        ->mapErr(static fn($fault) => new SpecializedFault(
            sprintf('Could not run operation: %s', (string) $fault)
        ));
}
```

#### `$result_instance->unwrapOr(mixed $default_value): mixed`

`unwrapOr` returns the "inner" `Ok` value or returns `$default_value` if called on `Err`.

If called on an `Ok`, it returns its "inner" value.

If called on an `Err`, it returns `$default_value`.

Parameters:
- `mixed $default_value`: A default value to return if this function is called on an `Err`.

Returns: `mixed`. It returns either the "inner" `Ok` value or `$default_value`.

Example:
```php
/** @returns Ok<string> | Err<Fault> */
private function aFunctionThatMightFail(): Ok|Err
{
    return Result::err(Fault::fromMessage("Ooops"));
}

private function defaultValue(): void
{
    $value = $this->aFunctionThatMightFail()->unwrapOr('Default value');
    // $value holds 'Default value'
}
```

## Links

* [ADR-0012: Favor Faults over Exceptions][0]
* [ADR-0013: NeverThrow][1]

[0]: ../../../adr/0012-faults-over-exceptions.md
[1]: ../../../adr/0013-neverthrow.md

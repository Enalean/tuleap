# NeverThrow

* Status: accepted
* Deciders: Joris MASSON (@jmasson)
* Date: 2022-06-20

Technical Story: [story #26802 have smart commit with Tuleap Git][1]

## Context and Problem Statement

[Faults][0] make errors more visible by moving them to functions' signatures. However, manual error propagation becomes annoying quickly. Can we avoid wrapping every function call in a `if` checking if there is an error, while still making it visible that functions can return errors ?

## Decision Outcome

Inspired by functional programming, [neverthrow][2], a TypeScript library, strives to make error-handling easier by representing return values as a union type of "Error or Value". In TypeScript, we use a type alias to represent this union as  a `Result` type. A `Result` is equivalent to the union type `Ok | Err`. `Ok` represents a return value in a "success" state. `Err` represents a return value in an "error" state.

Both implement the same methods, but each variant skips methods that are not meant for it. For example, both `Ok` and `Err` implements `map()` but calling `map()` on an `Err` variant will do nothing. A method meant to work on an `Ok` will be skipped (it will do nothing) on an `Err`, and vice-versa. Both implement a way to chain `Result`s together: if the first `Result` is an `Ok`, the second call will be executed. If not, the entire chain will return the first `Err`.

Leveraging this, we can propagate errors more easily by returning in case we only have to call one function that might fail.

```php
// Instead of writing this:
interface Checker
{
    public function itCouldFail(): Fault | null;
}

class Upper {
    private Checker $checker;

    public function upper(): Fault | null
    {
        $result = $this->checker->itCouldFail();
        if ($result instanceof Fault) {
            return $result;
        }
    }
}
```
```php
// We can write this:
interface Checker
{
    public function itCouldFail(): Ok|Err;
}

class Upper {
    private Checker $checker;

    public function upper(): Ok|Err
    {
        return $this->checker->itCouldFail();
    }
}
```

If we have more than one function calls that can return errors, we can chain them together using the `andThen()` method.

```php
class Executor
{
    public function executeSeveralOperationsThatCouldFail(): Ok|Err
    {
        return $this->checker
            ->itCouldFail()
            ->andThen(fn($result) => $this->other_checker->itCouldAlsoFail($result))
            ->andThen(fn($second_result) => $this->store->saveResult($second_result));
    }
}
```

By chaining calls that can fail together, we can group together error-handling for many function calls in one place. Only in that single place, we distinguish what kind of error it is and handle each kind.

```php
public function handleErrors(): RestException | null
{
    $result = $this->executor->executeSeveralOperationsThatCouldFail()
    if (Result::isErr($result)) {
        if ($result->error instanceof FirstCheckerFault) {
            // return HTTP error code 400
            return new RestException(400, (string) $result->error);
        }
        if ($result->error instanceof StoreSaveFault) {
            // log the fault
            $this->logger->error((string) $result->error);
        }
    }
    return null;
}
```

Taking inspiration from TypeScript [neverthrow][2], we introduce a port to PHP: [Tuleap\NeverThrow][3].

### Recommendations and rules

* Functions that can have errors should return `Ok | Err`.
* The error type wrapped in an `Ok | Err` should always be `Fault`.
* The value type wrapped in an `Ok | Err` can be anything we're used to: a value-object, a Presenter, a JSON Representation, …

### Positive Consequences

* Errors are still part of the public signature of functions.
* It is still easier to gather lists of errors (as opposed to Exceptions).
* It does not force every caller to do error-handling (as opposed to plain `Fault` usage). Error-handling can be grouped in a top-level function.

### Negative Consequences

* In PHP, every function that can fail has the same return type: `Ok | Err`.
* As PHP does not support type aliases, we must repeat the union type `Ok | Err`
* Since PHP does not natively support generics, we must add doc-blocks to have more precise return types: `@returns Ok<Value> | Err<Fault>`.
* It introduces a new way of representing errors that will coexist with existing ways (Exceptions, booleans, null) for a very long time, leading to reduced consistency.
* If a return value is ignored, the error is silenced. Silencing the error is implicit instead of being explicit (with empty catch clause).

## Links

* Refines [ADR-0012: Favor Faults over Exceptions][0]
* Supermacro's TypeScript [neverthrow][2] library
* [Tuleap\NeverThrow\Result][3]: a PHP implementation of neverthrow

[0]: ./0012-faults-over-exceptions.md
[1]: https://tuleap.net/plugins/tracker/?aid=26802
[2]: https://github.com/supermacro/neverthrow
[3]: ../src/common/NeverThrow/README.md

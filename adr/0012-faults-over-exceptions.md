# Favor Faults over Exceptions

* Status: accepted
* Deciders: Joris MASSON (@jmasson)
* Date: from 2021-11-19 to 2022-06-20

Technical Story: [story #26802 have smart commit with Tuleap Git][0]

## Context and Problem Statement

Handling errors is hard. Historically, we have come up with several solutions to indicate technical or business problems in our programs.

1. `return boolean`
2. `throw Error`
3. `return null`

Returning booleans works well when there are only two cases, but the semantics become muddy when there are three cases. Is it `false` because the user does not have permission or because it does not exist ?

Sometimes, we write code that returns `SomeType|null`. This is not ideal though, because `null` could hide several problems. For example, if I build a `Project` from an `int`, depending on the context, we might want to distinguish between "the given integer does not match any project" and "the current user does not have permission to see that project". However, since we must return a valid object or `null`, both cases become represented by `null` and we lose that distinction.

Another solution is to use exceptions with `Throwable`. `try/catch` statements can filter on exception type. This lets us distinguish between errors and for example return a `400 Bad Request` or a `403 Forbidden` depending on the error. However, [exceptions have several issues][1].

In Tuleap, there can be _a lot_ of different configuration issues, so it's important for us to collect as many of them as we can to help our end-users fix them all instead of one by one. There can only be one exception in-flight at any given time. Having only one exception at a given time makes it much harder to collect multiple errors. We need to come up with creative solutions to `try/catch` exceptions and keep going to collect them all.

Additionally, it is not clear whether to use _checked_ or _unchecked_ exceptions, which leads to further confusion. Some of our business errors are _unchecked_ exceptions (for example extending `\RuntimeException` or `\LogicException`). In that case, our IDE tools _do not_ warn us that we have forgotten to declare them in a docblock `@throws`, or worse, that we have forgotten to handle them. This will result in a `500 Internal Server Error`. Unchecked exceptions make handling errors harder for us because we can't tell if any function call may throw an exception.

Exceptions are a bit similar to a `goto` statement. They break the control flow of a program and bubble up until caught (or produce a fatal error, which is worse). Sometimes, static analysis cannot track that a function might throw. For example in the case of events, static analysis has no way to know that an event handler could throw (and which kind of exception it could throw). Neither Psalm nor my IDE can warn me that the call might throw, even though I am using a _checked_ exception.

```php
class MyException extends \Exception {}

// plugin class
/** @throws MyException */
function onMyEvent(MyEvent $event): void
{
    throw new MyException();
}

// core class
$event = new MyEvent();
$event_manager->dispatch($event); // <- This will throw, but Psalm or my IDE cannot warn me about it. I can only know by reading the code.
```

Finally, the semantics of exceptions have become quite muddy. Exceptions have become very commonplace, so anything can be an exception, from a failure to communicate with the database (which is most likely fatal) to "no change when saving a new changeset for an artifact". The former is rightly an exception, because it is unlikely that the calling function will know how to fix it. No program can guess that you gave the wrong database password and fix it for you, for example (if it can fix this, something is wrong). The latter is ignored more often than it is handled, so this raises the question: Should it be an Exception if we don't need to care about it ? Using exceptions to represent every kind of error has been pushed even by the [PHP Manual][6].

The examples feature PHP code, but the same problems exist in TypeScript. JavaScript `Error`s are Exceptions. The situation is even worse there, because the language does not allow to filter errors in a `catch` expression. When we catch errors, we also catch programmer mistakes (which we don't want to catch) and thus we always re-throw errors to have a chance to see them in the console.

Can we handle errors with more than `bool` or `null` checks ? Can we avoid `Throwable` when dealing with non-fatal errors ?

## Decision Outcome

In the Go language, errors are values, like strings or booleans. They are returned, not thrown. Calling code can decide whether to deal with the error or not. There are no exceptions in Go. If a problem is fatal and the program can no longer continue its execution, it can `panic()`.

There is nothing preventing us from using regular objects as errors in PHP or TypeScript. Instead of throwing Exceptions or returning `null`, we can return instances of an `Error` class. Even though we don't have multi-return, we can still leverage union types and return `Value|Error`.

Objects let us keep the usual control-flow: we use `return` instead of `throw`. As a result, errors are part of the function signature. Our static analysis tools force us to write return types on new code, which means that just by reading the function signature, we will know that it can have errors (as opposed to Exceptions where we have to dig deep in the code).

Using objects, we can collect many errors at the same time. We are not limited to only one error at a time.

Semantically, using objects and returning them communicates that non-catastrophic errors are part of the normal flow of the program. Having a permission check denied is not an _exceptional_ situation, it happens all the time.

Taking inspiration from Go and from [Dave Cheney's Go errors package][5], we introduce the `Fault` class. A `Fault` is a business or technical error that is not fatal and can be recovered from. The name `Fault` is used to distinguish from the `Error` concept in PHP (like fatal errors).

```php
use Tuleap\NeverThrow\Fault;

// plugin class
function onMyEvent(MyEvent $event): ?Fault
{
    return Fault::fromMessage('An error occurred');
}

// core
class EventManagerWithFault
{
    public function dispatchEventWithFault(\Tuleap\Event\Dispatchable $event): ?Fault
    {
        //...
    }
}

$event_manager_with_fault = new EventManagerWithFault();
$event = new MyEvent();
$result = $event_manager_with_fault->dispatchEventWithFault($event);
// $result is Fault | null
```

In PHP, since the type-system is [nominal][11], we create subclasses of `Fault` to differentiate between different errors. We can add properties to the error subclasses. We can distinguish between error types with `if instanceof` instead of `catch`.

In TypeScript, since the type-system is [structural][12], we can go further and detect behaviour on `Fault`s and keep them completely opaque, without using classes at all. It has the benefit of removing the dependency between the code producing the `Fault` and the code handling it. We don't even need to import a type of error when handling it, we just check the Fault's structure.

### Recommendations and rules

* Faults are always immutable.
* Errors that the program has no way of recovering from should still be Exceptions. For example, being unable to connect to the database.
* Programmer mistakes should still be Exceptions, so that they are harder to miss.

### Positive Consequences

* Errors are part of the public signature of the function, instead of being in a doc-block (or undocumented).
* It is easier to gather lists of errors.
* In TypeScript, programmer mistakes are no longer accidentally caught (and silenced) by error-handling.

### Negative Consequences

* It introduces a new way of representing errors that will coexist with existing ways (Exceptions, booleans, null) for a very long time, leading to reduced consistency.
* If a return value is ignored, the error is silenced. Silencing the error is implicit instead of being explicit (with empty catch clause).
* It multiplies the number of `if` blocks dedicated to error-handling. For each function call returning a potential error, you need to check whether it is an error or not and propagate an error manually by returning it. See [Tim Penhey's blog post][10] for an example in Go. [ADR-0013: NeverThrow][7] addresses this issue.

## Links

* Refined by [ADR-0013: NeverThrow][7]
* [@tuleap/fault][8]: a TypeScript implementation of Faults
* [Tuleap\NeverThrow\Fault][9]: a PHP implementation of Faults
* Dave Cheney's 2012 blog post on [Why Go gets exceptions right][1]
* Dave Cheney's 2014 blog post on [Error handling vs. exceptions redux][2]
* Dave Cheney's 2015 blog post on [Errors and Exceptions, redux][3]
* Dave Cheney's 2016 blog post on [Don’t just check errors, handle them gracefully][4]
* Dave Cheney's [Go errors package][5]
* Tim Penhey's 2013 blog post on [The Go Language - My thoughts][10]. The "Error handling" paragraph offers some criticism of Go's error handling.

[0]: https://tuleap.net/plugins/tracker/?aid=26802
[1]: https://dave.cheney.net/2012/01/18/why-go-gets-exceptions-right
[2]: https://dave.cheney.net/2014/11/04/error-handling-vs-exceptions-redux
[3]: https://dave.cheney.net/2015/01/26/errors-and-exceptions-redux
[4]: https://dave.cheney.net/2016/04/27/dont-just-check-errors-handle-them-gracefully
[5]: https://github.com/pkg/errors
[6]: https://www.php.net/manual/en/language.exceptions.php#language.exceptions.examples
[7]: ./0013-neverthrow.md
[8]: ../lib/frontend/fault/README.md
[9]: ../src/common/NeverThrow/README.md
[10]: http://how-bazaar.blogspot.com/2013/04/the-go-language-my-thoughts.html
[11]: https://en.wikipedia.org/wiki/Nominal_type_system
[12]: https://en.wikipedia.org/wiki/Structural_type_system

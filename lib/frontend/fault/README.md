# Fault

A better base type for error handling.

## Usage

There are three ways to create a `Fault`:

### `Fault.fromMessage(message: string): Fault`

`fromMessage` returns a new Fault with the supplied message. It also records the stack trace at the point it was called.

Parameters:
- `message: string`: A message to explain what happened. This message could appear in console logs or on-screen.

Returns: `Fault`

Example:
```typescript
const fault = Fault.fromMessage("User does not have permission");
```

### `Fault.fromError(error: Error): Fault`

`fromError` wraps an existing Error and returns a new Fault with its message and stack trace. It preserves both the message and the stack trace from `error`.

Parameters:
- `error: Error`: Wrapped error

Returns: `Fault`

Example:
```typescript
const fault = Fault.fromError(functionThrowingAnError());
```

### `Fault.fromErrorWithMessage(error: Error, message: string): Fault`

`fromErrorWithMessage` wraps an existing Error and returns a new Fault with the supplied message. It discards the message from `error`. It preserves the stack trace from `error`.

Parameters:
- `error: Error`: Wrapped error
- `message: string`: A message to explain what happened. This message could appear in console logs or on-screen.

Returns: `Fault`

Example:
```typescript
const fault = Fault.fromErrorWithMessage(
    functionThrowingAnError(),
    "Could not retrieve data from the HTTP API"
);
```

### `isFault(param: unknown): param is Fault`

`isFault` verifies that an unknown object is a Fault. It can be useful to deal with rejected promises for example, where you do not know if the rejection reason is a Fault or an Error.

Parameters:
- `param: unknown`

Returns: `true` if `param` is a `Fault`

Example:
```typescript
promise.catch((error: unknown) => {
    if(isFault(error)) {
        console.log(String(error));
    }
});
```

### `fault_object.toString(): string`

`toString()` allows casting the Fault to `string`.

Example:
```typescript
const fault = Fault.fromMessage("User does not have permission");
console.log(String(fault));
```

### `fault_object.getStackTraceAsString(): string`

`getStackTraceAsString` allows to print a stack trace, like an `Error`. Useful for debugging.

Example:
```typescript
const fault = Fault.fromError(functionThrowingAnError());
console.log(fault.getStackTraceAsString());
```
### `fault_object[string]: () => boolean | string`

See the [Specialized Fault](#specialized_fault) paragraph below.

## Examples

### `throw -> return`

```typescript
// Instead of writing this:
function itCouldFail(): void {
    throw new Error("User cannot see this page");
}

// We can write this:
function itCouldFail(): Fault | null {
    return Fault.fromMessage("User cannot see this page");
}
```

### `catch -> if`

```typescript
// Instead of writing this:
try {
    itCouldFail()
} catch(error: Error) {
    console.log(e);
}

// We can write this:
result = itCouldFail();
if (result) {
    console.log(String(result));
}
```

### `throw -> return`

```typescript
// Instead of writing this:
function itReturnsValueOrFails(id: number): CustomValue {
    if (!isValid(id)) {
        throw new CustomError("Not valid");
    }
    return CustomValue(id);
}

// We can write this:
function itReturnsValueOrFails(id: number): CustomValue | Fault {
    if (!isValid(id)) {
        return Fault.fromMessage("Not valid");
    }
    return CustomValue(id);
}
```

<span id="specialized_fault"></span>
### Specialized `Fault`

Faults can also be extended to add methods that return `boolean` or `string`. It helps distinguish faults based on their _behaviour_ instead of their _type_. Treating Faults like opaque values this way allows to decouple code handling Faults from code producing them. We don't have to `import` anything, we just have to know that the produced Fault will have a method `isPermissionDenied()` that returns `true` to handle this specific kind of Fault. If we want to add another error that should be handled the same way, we can create a second type of Fault with the same method.

Example:
```typescript
const PermissionFault = (): Fault => {
    const fault = Fault.fromMessage("Permission denied");
    return {
        isPermissionDenied: () => true,
        ...fault
    };
};
```
```typescript
// In Fault handling code
const isPermissionDenied = (fault: Fault): boolean =>
    "isPermissionDenied" in fault && fault.isPermissionDenied() === true;

if (isPermissionDenied(fault)) {
    // Ignore specifically Permission Denied error
} else {
    console.log(String(fault));
}
```

See the [Errors as opaque values](#opaque_values) paragraph for more details.

## Why `Fault` instead of `Error` ?

### Existing patterns

Handling errors is hard. Historically, we have come up with several ways of handling errors in our programs.

1. `return boolean`
2. `throw Error`
3. `return null`

Returning `boolean` works if there are only two cases (unless you're SQL). That is a bit too limited.

Throwing `Error` is good. `Errors` have a stack trace to help us pinpoint where the problem comes from. We can create subclasses to account for the different cases. We can add more properties to subclasses: `userId`, etc.

However, throwing `Error` is also bad. JavaScript (and TypeScript) does not let us filter on the type of `Error`. `catch` is all-or-nothing. Did you cause a `SyntaxError` or a `ReferenceError` somewhere ? Boom, it ends in your `catch`, instead of blowing up your program and warning you in the dev tools console, as it should. As a result, we sometimes catch only to re-throw the error later, so that it _does_ appear in the dev tools console.

Our IDE never warns us that functions might throw `Error`, because we never bother to write `@throws` in a docblock above. Throwing an error is not part of the function signature, so when you call a function, you never know if it's going to throw.

`throw` also breaks the normal control-flow. It is a bit equivalent to `goto`, where you go to the nearest `catch` in the call stack. There can _only be one_ `Error` thrown at a given moment. This means that it's _extra painful_ to collect a list of `Errors`, for example to display a list of configuration problems. To build the list, you have to `catch` every time you call a function.

Throwing `Error` sometimes also raises semantic problems. Is it really an _Error_ that a user is denied access because they don't have the necessary permission ? Is it an error that `NaN` is not a valid ID in your database ? It shouldn't be an error but the expected outcome of your program.

The third pattern of returning `null` can work too, but it's a bit similar to `boolean`. We lose track of _why_ something is not valid or allowed because in each case we will get `null`.

### Handling errors with regular objects

In Go, there is no exception-like `Error`. There is a base `error` type and functions can return multiple values.

We can consider errors as regular objects too in TypeScript. Treating errors as regular objects allows us to preserve the usual control-flow. We can use `return` instead of `throw`. Errors can be part of the function signature in TypeScript. If your linter mandates that your functions have a return type, you will be forced to document that a function can return a `Fault`. Users of that function will know that it can result in an error and they will have to deal with it.

Using regular objects means it's also easier to collect a list of problems. We can create specialized types of `Faults` to account for the different cases (see an example in the [unit tests][7]). We can also add more properties and make specialized `Faults` implement interfaces. Using TypeScript's type guard methods, we can filter on the behaviour of the `Fault` (again, see an example in the [unit tests][7]).

On a semantic front, it makes more sense: non-catastrophic errors are regular objects and are part of the _expected outcome_ of the program. They are part of the signature, like other expected return values.

`Errors` are still necessary for problems that the program has _no way_ of resolving by itself. For example, running out of memory, when there is a syntax error in the code, etc. For _all other cases_, we should use `Fault` objects.

<span id="opaque_values"></span>
### Errors as opaque values

Even though it is tempting to test for the type of error using `instanceof`, we should not. TypeScript uses a [Structural type system][6] that lets us assert an object implements an interface if it has at least the same properties defined by it. This means we can write functions that test an unknown object for some properties and if the property is present, we can say that it implements the interface.

This allows us to treat `Faults` as opaque values and only try to differentiate them at the end of the call stack, when needed.

```typescript
// We only need to distinguish types of Faults when handling them.
// Instead of writing this:
function itCouldFail(): PermissionDenied | NotFound | InvalidData | null {}

// We can write this:
function itCouldFail(): Fault | null {}

interface PermissionDenied extends Fault {
    isNotFound: () => true;
}

interface NotFound extends Fault {
    isNotFound: () => true;
}

interface InvalidData extends Fault {
    hasInvalidData: () => true;
}
```

```typescript
function handleErrors(): void {
    const fault = itCouldFail();
    // Assert Faults for behaviour, not type. This way, error-handling code does not have to depend on types from other parts of the program.
    // There is nothing to "import". It only depends on the behaviour of the Fault object.
    if ("isNotFound" in fault && fault.isNotFound() === true) {
        displayErrorNotFound();
        return;
    }
    if ("hasInvalidData" in fault && fault.hasInvalidData() === true) {
        displayInvalidDataError();
        return;
    }
}
```

## Links

* Dave Cheney's 2012 blog post on [Why Go gets exceptions right][1]
* Dave Cheney's 2014 blog post on [Error handling vs. exceptions redux][2]
* Dave Cheney's 2015 blog post on [Errors and Exceptions, redux][3]
* Dave Cheney's 2016 blog post on [Don’t just check errors, handle them gracefully][4]
* Dave Cheney's [Go errors package][5]

[1]: https://dave.cheney.net/2012/01/18/why-go-gets-exceptions-right
[2]: https://dave.cheney.net/2014/11/04/error-handling-vs-exceptions-redux
[3]: https://dave.cheney.net/2015/01/26/errors-and-exceptions-redux
[4]: https://dave.cheney.net/2016/04/27/dont-just-check-errors-handle-them-gracefully
[5]: https://github.com/pkg/errors
[6]: https://en.wikipedia.org/wiki/Structural_type_system
[7]: <./src/Fault.test.ts>

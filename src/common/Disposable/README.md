# Disposable

[Dispose pattern][0] for PHP.

### Usage

Code using a resource that needs to be freed or cleaned-up should implement the `Disposable` interface. ⚠️ Make sure that the code run in `dispose()` method is idempotent (can be run more than once).

Code that needs to access a Disposable should call `Dispose::using()`. `dispose()` will be called at the end of the given callback.

#### `Dispose::using(Disposable $disposable, callable $fn): mixed`

`using()` calls `$fn` with `$disposable` as its first argument, and at the end of the function, it calls `$disposable->dispose()`.

It returns the return value of `$fn`. If an exception or an error is thrown in `$fn`, it will still call `$disposable->dispose()`.

Parameters:
- `$fn`: `function(Disposable $disposable): mixed`. A function that uses `$disposable`. It will receive a single argument: the Disposable `$disposable`.

Returns: `mixed`. It returns what is returned by `$fn`.

[0]: https://en.wikipedia.org/wiki/Dispose_pattern

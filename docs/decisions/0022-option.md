# Option

* Status: accepted
* Deciders: Thomas GERBET, Joris MASSON, Nicolas TERRAY, Manuel VACELET
* Date: 2023-03-07

Technical Story: [request #31117 Introduce a generic `Option<T>` type][0]

## Context and Problem Statement

The semantics of `null` are muddy. Historically, we have used it to represent the _absence_ of a value. `SomeType|null` (shortened to `?SomeType` in PHP) often means "SomeType or nothing". However, we have also used it to [indicate technical or business problems][9], in which case the `null` masks several possibilities. It can also sometimes be a valid value.

Returning `SomeType|null` means the caller must each time perform a null-check when they want to handle the value (or its absence). It leads to a proliferation of null-checks everywhere, as sometimes a `null` leads to other `null`s up the stack, for example with objects that depend on another object to be built. A quick search for null-check patterns in our PHP codebase yields thousands of results. The same issue exists in TypeScript, but made worse by the addition of `undefined` to the mix.

Can we find a better way to represent the _absence_ of a value and avoid the proliferation of null-checks ?

## Decision Outcome

Inspired by functional programming, we introduce [PHP][2] and [TypeScript][3] implementations of the `Maybe`/`Option` type present in functional programming languages. See [Haskell's Maybe][4] or [Rust's Option][5] for examples.

`Option` is either a `Some` variant (holding a value) or a `None` variant (holding nothing). Both implement the same methods, but each variant skips methods that are not meant for it. For example, both `Some` and `None` implement `apply()` but calling `apply()` on a `None` variant will do nothing. A method meant to work on a `Some` variant will be skipped (it will do nothing) on a `None` and vice-versa.

Leveraging this, we can stop writing null-checks every time we might get no value.

```php
// Instead of writing this:
interface Retriever
{
    public function itCouldReturnNothing(): SomeValue | null;
}

class Upper
{
    private Retriever $retriever;

    public function upper(): void
    {
        $value = $this->retriever->itCouldReturnNothing();
        if ($value !== null) {
            // Do something with $value
        }
    }
}
```

```php
// We can write this:
interface Retriever
{
    /**
     * @return Option<SomeValue>
     */
    public function itCouldReturnNothing(): Option;
}

class Upper
{
    private Retriever $retriever;

    public function upper(): void
    {
        $this->retriever->itCouldReturnNothing()->apply(function (SomeValue $value) {
            // Do something with $value
            // This will not be executed if Retriever returns a None variant
        });
    }
}
```

### Recommendations and rules

* Functions that can return a value or nothing should return `Option`.

### Positive Consequences

* The possible absence of value is better communicated by the return type.
* It avoids using `null` (or `undefined` in TypeScript) to indicate a value might not be present. `null` can be a valid value or have a different meaning than "missing" (for example, it can be a "to be removed")
* It does not force every caller to check for `null`: using built-in functions, calling code can add behaviour that will be skipped when the Option is a `None` variant.
* It reduces conditional logic and thus reduces testing efforts.

### Negative Consequences

* Since PHP does not natively support generics, we must add doc-blocks to have more precise return types: `@returns Option<TypeOfValue>`.
* It introduces a new way of representing the possible absence of value that will coexist with existing ways (`null`, `undefined`) for a very long time, leading to reduced consistency.

## Considered Options

* Pull `Option` from external libraries
* Write an implementation ourselves

Chosen option: "Write an implementation ourselves" because it comes out best in the comparison (see below).

## Pros and Cons of the Options

### Pull `Option` from external libraries

We could pull existing implementations from existing libraries, such as [azjezz/psl][6] for PHP or [fp-ts][7] for TypeScript.

* Good, because it's less initial work as we don't have to write code.
* Bad, because it's very difficult to find libraries with similar API across PHP and TypeScript. It would lead to reduced consistency. For example, the API of `fp-ts` is all about importing each function you need, while our PHP implementation relies on a single class. Other options like [ts-opt][10] or [option-t][11] have very different (and incompatible) APIs, with the former using a class-like, chainable method pattern and the latter exporting individual functions.
* Bad, because it comes with the risk of future breaking changes that we will be forced to adapt to.
* Bad, because relying a lot on big libraries such as `azjezz/psl` or `fp-ts` comes with a risk of "overusing" them. It echoes our history with [lodash][8]. It was a big library, it simplified very common patterns (such as mapping arrays), until the most common operations were included in the standard library of ES2015. After that point, the value brought by lodash had become much lower, it was now too heavy for its value. Thus, we started removing usages of lodash, but there were so many it became a very long work. For such a common pattern as "handling the absence of value", it seems risky to base so much code on a big external library.
* Bad, because creating a `None` variant with `azjezz/psl` gives `Option<never>`, which makes us "force" the proper type with a `@var` annotation.
* Bad, because we had already implemented `Option` in PHP before starting to use `azjezz/psl`. We would need to adjust that code to the new API.

### Write an implementation ourselves

We could write implementations ourselves, as we have already done for [`Result`][1].

* Good, because we can make the APIs consistent across PHP and TypeScript (save for the differences inherent to the languages).
* Good, because it's not very complicated code. The main challenge lies in the type system.
* Good, because if we need to do breaking changes, we can adjust all dependent code in the codebase at the same time.
* Bad, because it's more initial work.

## Additional notes

* Java developers: our `Option<T>` is different to your `Optional<T>` since PHP and TypeScript have a decent handling of `null` values. `Optional<T>` means `T` or `null`. `Option<T>` means `T` or the absence of value.

## Links

* Expands on [ADR-0013: NeverThrow][1] and [ADR-0012: Faults over Exceptions][9]
* [PHP implementation][2]
* [TypeScript implementation][3]

[0]: https://tuleap.net/plugins/tracker/?aid=31117
[1]: 0013-neverthrow.md
[2]: ../../src/common/Option/README.md
[3]: ../../lib/frontend/option/README.md
[4]: https://wiki.haskell.org/Maybe
[5]: https://doc.rust-lang.org/std/option/enum.Option.html
[6]: https://github.com/azjezz/psl
[7]: https://gcanti.github.io/fp-ts/
[8]: https://lodash.com/
[9]: 0012-faults-over-exceptions.md
[10]: https://www.npmjs.com/package/ts-opt
[11]: https://www.npmjs.com/package/option-t

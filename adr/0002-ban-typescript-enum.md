# Ban TypeScript Enum syntax

* Status: accepted
* Deciders: Joris MASSON
* Date: 2021-04-13

Technical Story: [request #20917 Ban TypeScript Enums syntax][4]

## Context and Problem Statement

[Enums][1] have been a part of TypeScript ever since we started adopting it in Tuleap development. Naturally, we started
using them. There is a [TC39 Proposal][3] to add this syntax to the ECMAScript specification. However, at the time of
writing this document, the proposal has not moved from stage zero since 2018. Enums might never be added to ECMAScript...
Moreover, the [TypeScript Documentation][0] itself advises to "maybe hold off on using [Enums]". The question is then:
Should we ban TypeScript Enums usage ?

### Alternatives to Enums

Enums lead to more runtime code than alternatives. See for example this simple Enum with only two values:

```typescript
enum Direction {
    BEFORE = "before",
    AFTER = "after",
}
const dir = Direction.BEFORE;
if (dir === Direction.BEFORE) {}
```
Compiles to:
```javascript
var Direction;
(function (Direction) {
    Direction["BEFORE"] = "before";
    Direction["AFTER"] = "after";
})(Direction || (Direction = {}));
const dir = Direction.BEFORE;
if (dir === Direction.BEFORE) { }
```
See in [TypeScript Playground](https://www.typescriptlang.org/play?ssl=6&ssc=33&pln=1&pc=1#code/KYOwrgtgBAIglgJ2AYwC5wPYigbwLABQUxUAQgKIBiA8gErlQC8UARAEbABmGSLANIRJQAgpQAq5Wk1YBDTqmAJ+hAL6FkWAM6ooAE0TT4SNJhAA6CjXoBuQnE5QAFPoRNGzIynRYLVOuQBKXBUgA)

This Enum can completely be replaced by string constants and union types. It leads to less runtime code:

```typescript
type Direction = "after" | "before";
const AFTER: Direction = "after";
const BEFORE: Direction = "before";

const dir = BEFORE;
if (dir === BEFORE) {}
```
Compiles to:
```javascript
const AFTER = "after";
const BEFORE = "before";
const dir = BEFORE;
if (dir === BEFORE) { }
```
See in [TypeScript Playground](https://www.typescriptlang.org/play?#code/C4TwDgpgBAIglgJwgY2HA9gOygXigIgEMAzYCBfKAHwICMJj0l8BuAWAChksBnYKAIIAxACoBRAEoAuWIhRosuAiTIV2XXvwBCYoQHkJYmfCSoM2PPnqNm6zt0x8oAE0RKd+w+rjEoAClcEXBw8DwMxAEooAG8AXyA)

Enums can also be replaced by plain old Object, which again leads to less runtime code. It is a bit more verbose than
plain string constants though:

```typescript
const Direction = {
    BEFORE: "before",
    AFTER: "after",
} as const;
type DirectionType = typeof Direction[keyof typeof Direction];

const dir = Direction.BEFORE;
if (dir === Direction.BEFORE) {}
```
Compiles to:
```javascript
const Direction = {
    BEFORE: "before",
    AFTER: "after",
};
const dir = Direction.BEFORE;
if (dir === Direction.BEFORE) { }
```
See in [TypeScript Playground](https://www.typescriptlang.org/play?#code/MYewdgzgLgBAIgSwE4FNhQeGBeGBvAWACgZSYAhAUQDEB5AJUoC4YAiAIxQDMRVWAaYmRgBBagBVK9FqwCGXKCiQDiAXxiyIMUJCgBuYlACeABxTxkaDOHGnzuY2ZBcLqdJjABtANYojzmEcUAMQ3azAAXQMiYh1oGAATZBxXKw8AOio6RmiEFwAKJKQcbFxQtPBMmgZKAEp8VSA)

## Considered Options

* Ban Enums syntax usage
* Status quo: Keep allowing Enums syntax usage

## Pros and Cons of the Options

### Ban Enums syntax usage

Using an `eslint` rule, we are able to ban usage of Enums.

* Good, because alternatives produce fewer lines of runtime code.
* Good, because it avoids reliance on a TypeScript syntax that does not exist in JavaScript, as advised (see [here][0]
  and [here][2]) by the TypeScript handbook itself.

### Status quo: Keep allowing Enums syntax usage

* Good, because Enums offer a straightforward syntax provided by the TypeScript language.
* Bad, because Enums lead to more runtime code than alternatives.
* Bad, because it makes our TypeScript code depend on language syntax that might never be available in JavaScript.

## Links

* [TypeScript handbook page on Enums][0]
* [TypeScript handbook Enums syntax][1]
* [TypeScript handbook Objects vs Enums][2]
* [TC39 ECMAScript enums proposal][3]

[0]: https://www.typescriptlang.org/docs/handbook/2/everyday-types.html#enums
[1]: https://www.typescriptlang.org/docs/handbook/enums.html
[2]: https://www.typescriptlang.org/docs/handbook/enums.html#objects-vs-enums
[3]: https://github.com/rbuckton/proposal-enum#status
[4]: https://tuleap.net/plugins/tracker/?aid=20917

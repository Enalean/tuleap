# Option

A TypeScript implementation of the `Maybe`/`Option` type present in functional programming languages. See [ADR-0022: Option][0] for more context.

## Usage

Functions returning a value can use `Option.fromValue(...)` and `Option.nothing<TypeOfValue>()`.

Example:

```typescript
import { Option } from "@tuleap/option";

function getOptionalDataset(element: HTMLElement): Option<string> {
    const dataset = element.dataset.optional;
    if (!dataset) {
        return Option.nothing<string>();
    }
    return Option.fromValue(dataset);
}
```

You can then use the resulting value using `.apply()`:
```typescript
const value = getOptionalDataset(mount_point);
value.apply((dataset: string): void => {
   // dataset is defined, do something with it
});
```

At the end of a processing pipeline, you might want to retrieve the unwrapped value with `.mapOr()`:

```typescript
import { Fault } from "@tuleap/fault";
import { ok, err } from "neverthrow";
import type { Ok } from "neverthrow";

const value = getOptionalDataset(mount_point);
value.mapOr(
    (dataset: string): Ok => ok(dataset),
    err(Fault.fromMessage("Dataset is missing")),
);
```

In unit tests when the inner value is a primitive or an array, and you want to run assertions, use `.unwrapOr()`:

```typescript
const value = getOptionalDataset(mount_point);
expect(value.unwrapOr(null)).toBe("dataset-value");
```

## Links

* [ADR-0022: Option][0]

[0]: ../../../adr/0022-option.md

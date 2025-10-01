# @tuleap/vue-strict-inject


It offers an alternative to [`inject()`](https://vuejs.org/api/composition-api-dependency-injection.html#inject)
that does not return `undefined`. If a value cannot be found, it throws.

## Usage

Usage is similar to `inject()`:

```js
import { strictInject } from "@tuleap/vue-strict-inject";

// ...

const value = strictInject(SYMBOL);
```

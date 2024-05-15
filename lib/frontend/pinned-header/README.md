# @tuleap/pinned-header

A library to pin header (`<header>` tag) as soon as the users scroll to
a given threshold. This removes the floating navbar buttons so that it
is easier to position sticky components.

## Installation

```
pnpm i @tuleap/pinned-header
```

## Usage

### Default usage

```typescript
import { DEFAULT_THRESHOLD, pinHeaderWhileScrolling } from "@tuleap/pinned-header";

pinHeaderWhileScrolling(DEFAULT_THRESHOLD);
```

**Note:** If you end up using this default example,
there is a high chance that Tuleap does already the
job automatically for you if you add the following
body class while displaying the header:
`has-sidebar-with-pinned-header`.

### Advanced usages

If your threshold is specific to your context, you
can give your own computation:

```typescript
import { pinHeaderWhileScrolling } from "@tuleap/pinned-header";

pinHeaderWhileScrolling(314);
```

You can specify additional classname to toggle each time
the status of the pinned header change. This classname
will be added/removed on `<header>` tag alongside the
`pinned` classname:

```typescript
import { pinHeaderWhileScrolling } from "@tuleap/pinned-header";

pinHeaderWhileScrolling(314, "my-custom-pinned-class");
```

# @tuleap/commonmark-popover

A custom element embedding a button whose role is to display a popover containing a commonmark cheat sheet.

To be used everywhere commonmark can be written in Tuleap.

## Usage:

Import the lib globally in your code:
``` Typescript
import "@tuleap/commonmark-popover";
```

Add the custom element to your DOM:
``` HTML
<tuleap-commonmark-popover/>
```

## Testing

When needed, you can import the following at the top of your test files:
``` Typescript
import "@tuleap/commonmark-popover/commonmark-popover-stub"
```

It will define an empty custom element with the same tag as the real commonmark-popover.

# @tuleap/mention

It enables auto-completion of `@username` for Tuleap usernames. For example, if you type
`@adm`, a list will be inserted near your cursor and will offer `@admin` to be selected.

Depends on `jquery`. Provide it as `externals` in webpack configuration:

```javascript
// webpack.config.js
{
    //...
    externals: {
        "jquery": "jQuery",
    },
    //...
}
```
Also, make sure to include jQuery sources in PHP **before** loading this module.

This lib also provides a CSS file at `dist/style.css`, make sure to include it:

```scss
@import '~@tuleap/mention';
```
## Usage:

```typescript
import { initMentions } from "@tuleap/mention";

const textarea: Element = document.getElementById("some-textarea");
// Enable auto-completion on the element. It only works on <textarea>,
// <input> or elements with attribute [contenteditable=true]
initMentions(textarea);

initMentions("#some-textarea"); // Enable auto-completion on a jQuery selector
```

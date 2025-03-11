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
This lib also provides a CSS file at `dist/mention.css`, make sure to include it:

```scss
@use "pkg:@tuleap/mention";
```
## Usage:

```typescript
import { initMentions } from "@tuleap/mention";

const textarea: HTMLElement|null = document.getElementById("some-textarea");
// Enable auto-completion on the element. It only works on <textarea>,
// <input> or elements with attribute [contenteditable=true]
if (textarea) {
    initMentions(textarea);
}

initMentions("#some-textarea"); // Enable auto-completion on a jQuery selector
```

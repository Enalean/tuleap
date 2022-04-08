# @tuleap/tooltip

It detects `<a>` tags with `cross-reference` and `direct-link-to*` CSS classes and creates Tooltips on them.
Tooltips appear when hovering those links with the cursor.

Depends on `jquery`. Provide it as `externals` in webpack configuration:

```javascript
// webpack.config.js
{
    //...
    externals: {
        "jquery": "jquery",
    },
    //...
}
```

Also, make sure to include jQuery sources in PHP **before** loading this module.

## Usage:

```javascript
import { loadTooltips } from "@tuleap/tooltip";

// Detect a.cross-reference and a[class^=direct-link-to] links and create Tooltips on them
loadTooltips();
```

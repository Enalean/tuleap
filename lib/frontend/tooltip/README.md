# @tuleap/tooltip

It detects `<a>` tags with `cross-reference` and `direct-link-to*` CSS classes and creates Tooltips on them.
Tooltips appear when hovering those links with the cursor.

## Usage:

```javascript
import { loadTooltips } from "@tuleap/tooltip";

// Detect a.cross-reference and a[class^=direct-link-to] links and create Tooltips on them
loadTooltips();
```

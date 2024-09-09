# @tuleap/url-sanitizer

Exposes a `sanitizeURL(url: string): string` function. Use it to sanitize untrusted urls coming from user inputs.

## Usage:

```javascript
import { sanitizeURL } from "@tuleap/url-sanitizer";

const url = document.getElementById<HTMLLinkElement>("untrusted-url-provided-by-current-user").href;
const sanitized_url = sanitizeURL(url);
```

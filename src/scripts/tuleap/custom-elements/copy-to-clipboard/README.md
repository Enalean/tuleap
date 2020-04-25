# <copy-to-clipboard> element

Copy the input value to the clipboard.

## Usage

```typescript
import "<...>/components/copy-to-clipboard";
```

```html
<copy-to-clipboard value="value-to-copy">
    Copy the value to the clipboard
</copy-to-clipboard>
```

If you need to support IE11 you will need to import the polyfill before the element itself:
```typescript
import "<...>/componenents/custom-elements-polyfill-ie11";
import "<...>/components/copy-to-clipboard";
```
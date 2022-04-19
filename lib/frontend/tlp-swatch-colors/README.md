# @tuleap/tlp-swatch-colors

## Usage

A SCSS library to share TLP Business colors. Include color classes in your themes and use "tlp-swatch-*" class names:

```html
<span class="tlp-swatch-inca-silver my-colored-element"></span>
```
```scss
@use "@tuleap/tlp-swatch-colors";

.my-colored-element {
    color: var(--text-color);
    background: var(--primary-color);
}
/* The following CSS custom properties are set:
    --primary-color
    --secondary-color
    --border-color
    --text-color
    --text-on-dark-color
    --accessibility-pattern
*/
```
## Overriding colors

Only in cases where you need to modify those colors, use the following SCSS snippet:

```scss
@use "@tuleap/tlp-swatch-colors";
@use "sass:map";
@use "sass:color";

@each $color-name, $colors in tlp-swatch-colors.$color-map {
    // This will create classes like .inca-silver, .deep-blue, etc.
    .#{$color-name} {
        border-bottom-color: #{color.adjust(map.get($colors, "border"), $lightness: - 20%)}
        // The following variants are available in the map:
        // "primary", "secondary", "border", "text", "text-on-dark"
        // and "pattern" which is an image pattern to help with accessibility
    }
}
```

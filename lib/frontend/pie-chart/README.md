# @tuleap/pie-chart

Makes it easier to create a d3 pie-chart.

## Usage

```typescript
import { StatisticsPieChart } from "@tuleap/pie-chart";

const pie_chart = new StatisticsPieChart({
    id : "pie-chart", // HTML id attribute
    prefix: "my-css-prefix", // CSS classes prefix
    general_prefix: "my-general-prefix", // CSS class prefix for the legend
    data,
    width: 300, // in pixels
    height: 250, // in pixels
    radius: 200 // in pixels
});

pie_chart.init(); // create the pie chart

// redraw the pie chart (for example on window resize)
pie_chart.redraw({ width: 200, height: 200, radius: 150 });
```

Data is passed as a JSON object. It looks like this:

```typescript
const data = [
    {
        key: "active", // The key will be used for CSS classes of that dataset (legend and slice).
        label: "Active users", // Translated text for the legend.
        value: 67, // The number that will produce the pie slice.
        color: 'tlp-success-color' // A CSS class that will set the CSS custom properties for the color of the slice and legend.
    },
    {
        key: "suspended",
        label: "Suspended users",
        value: 2,
        color: '' // Color is optional, provide an empty string if not used.
    },
    {
        key: "deleted",
        label: "Deleted users",
        value: 33,
        color: 'tlp-swatch-sherwood-green' // Tuleap Business colors (tlp-swatch) are also supported.
    },
    {
        key: "pending",
        label: "Pending users",
        value: 7,
        color: 'css-class-for-pending-users' // You can also bring your own CSS class as long as it sets the required CSS custom properties. See below.
    },
];
```

## Colors

By default, the chart supports the following class names for `color`:
- `''` Empty string (no color, will appear black)
- `tlp-danger-color`
- `tlp-success-color`
- `tlp-dimmed-color`
- `tlp-info-color`
- `tlp-warning-color`
- `tlp-swatch-inca-silver`
- `tlp-swatch-chrome-silver`
- `tlp-swatch-firemist-silver`
- `tlp-swatch-red-wine`
- `tlp-swatch-fiesta-red`
- `tlp-swatch-coral-pink`
- `tlp-swatch-teddy-brown`
- `tlp-swatch-clockwork-orange`
- `tlp-swatch-graffiti-yellow`
- `tlp-swatch-army-green`
- `tlp-swatch-neon-green`
- `tlp-swatch-acid-green`
- `tlp-swatch-sherwood-green`
- `tlp-swatch-ocean-turquoise`
- `tlp-swatch-surf-green`
- `tlp-swatch-deep-blue`
- `tlp-swatch-lake-placid-blue`
- `tlp-swatch-daphne-blue`
- `tlp-swatch-plum-crazy`
- `tlp-swatch-ultra-violet`
- `tlp-swatch-lilac-purple`
- `tlp-swatch-panther-pink`
- `tlp-swatch-peggy-pink`
- `tlp-swatch-flamingo-pink`

If you need a color outside of those, you can define your own CSS class in a stylesheet under your control and use the class name in `color` attribute.

The CSS class should define these custom properties for colors:
```scss
.css-class-for-pending-users {
    --tuleap-pie-chart-slice-color: rgb(255, 0 , 0); // This will set the color for the SVG path of the slice
    --tuleap-pie-chart-slice-text-color: var(--tlp-main-color); // This is the text near the SVG slice
    --tuleap-pie-chart-legend-color: oklch(0.82 0.17 187); // This is the color dot in the legend
}
```

## CSS classes to customize the chart's style

If you need to customize the chart's looks, it defines a number of CSS classes. All the classes are prefixed with a string that you provide in the setup (see above). The prefix is to avoid CSS classname clashes.

With `prefix = "my-css-prefix"` and `general_prefix = "my-general-prefix"`, it will produce the following CSS classes. `<key>` is replaced by the `key` property for each dataset.

```html
<!-- Each dataset has a slice -->
<g class="my-css-prefix-slice my-css-prefix-slice-<key>">
    <path class="my-css-prefix-slice-path"></path>
    <text class="my-css-prefix-slice-text"></text>
    <!-- If the slice is too small, the text can be "undisplayed" -->
    <text class="my-css-prefix-slice-text-undisplayed"></text>
</g>

<div id="my-css-prefix-legend">
    <ul class="my-general-prefix-legend">
        <!-- Each dataset has a legend item, "-selected" class is added on hover -->
        <li class="my-css-prefix-legend my-css-prefix-legend-<key> my-css-prefix-legend-selected">
            <span class="my-css-prefix-legend-color-span my-css-prefix-legend-color-<key>"></span>
            <span class="my-css-prefix-legend-text-span"></span>
        </li>
    </ul>
</div>
```

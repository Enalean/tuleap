# @tuleap/pie-chart

Makes it easier to create a d3 pie-chart.

## Usage

```typescript
import { StatisticsPieChart } from "@tuleap/pie-chart";

const pie_chart = new StatisticsPieChart({
    id : "pie-chart", // HTML id attribute
    prefix: "my-css-prefix", // CSS classes prefix
    general_prefix: "my-css-legend-prefix", // CSS class prefix for the legend
    data,
    width: 300, // in pixels
    height: 250, // in pixels
    radius: 200 // in pixels
});

pie_chart.init(); // create the pie chart

// redraw the pie chart (for example on window resize)
pie_chart.redraw({ width: 200, height: 200, radius: 150 });
```

## TLP color support

The chart supports assigning colors to chunks of data. To do so, in the `data` attribute when creating the chart, supply a "color":

typescript block (I cannot do it in this comment)
const data = [
  {
    key: "active", // The key will be used for CSS classes of that dataset.
    label: "Active users", // Translated text for the legend
    value: 67, // The number that will produce the pie slice
    color: 'tlp-success-color'
  },
  {
    key: "deleted",
    label: "Deleted users",
    value: 33,
    color: 'tlp-dimmed-color'
  }
];

Supported CSS class names are:
 - `tlp-danger-color`
 - `tlp-success-color`
 - `tlp-dimmed-color`
 - `tlp-info-color`
 - `tlp-warning-color`
 - `''` Empty string
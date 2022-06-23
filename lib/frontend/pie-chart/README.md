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

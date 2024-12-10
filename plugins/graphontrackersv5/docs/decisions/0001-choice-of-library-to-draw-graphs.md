---
status: accepted
date: 2024-12-06
decision-makers: Kevin TRAINI, Joris MASSON
consulted: Nicolas TERRAY, Thomas GORKA
informed: Thomas GERBET, Clarck ROBINSON, Marie Ange GARNIER, Manuel VACELET, Clarisse DESCHAMPS
---

# Choice of library to draw graphs

## Context and Problem Statement

Currently, we are using [D3][0] to draw bar, grouped bar, pie and cumulative flow graphs. Since the choice of using this library, graphs works well.

While working on [request #40462 Modernize GraphOnTrackersV5][1], we come to the typescript migration of the [graph loader script][2]. We face again an old question raised in [story #9463 see cumulative flow diagram][3]: the choice of graph library.

## Decision Drivers

* Feature coverage of the library. In many graphs, we need some kind of mouse-hover functionality. For example, in pie charts, we need the pie slice to "move" a bit and the legend to activate, to indicate to which category it corresponds.
* Those graphs exist since 2016, we should be careful not to break anything important in their design. This includes the use of colors (to avoid for example having colors too close from each other, as it makes it difficult to read the chart).
* Ease of maintenance.
* Consistency in Tuleap. We want to keep a visual consistency of charts for all Tuleap.
* Difficulty to test. Automated testing is sparse or non-existent for graphs. It's very hard to test something as visual as a graph, so we must rely more than usual on human review to ensure nothing is broken.

## Considered Options

* Keep [D3][0]
* Switch to [Chart.js][4]

## Decision Outcome

Chosen option: Keep D3. We found an old [gerrit #6571][5] patch with a discussion around the choice. It seems that D3 better fit our needs

## Pros and Cons of the Options

### Keep D3

* Good, because we already use it and it works.
* Neutral, it draws using svg.
* Bad, because it has a steep learning curve and requires to learn and manipulate a lot of concepts and APIs.

### Switch to Chart.js

* Good, because its API is simple to use.
* Neutral, it draws using a canvas.
* Bad, because it requires a full rewrite, and some bug may be introduced.

## More Information

This decision might and should be re-visited in the future to see if it is still the best available option.


[0]: https://d3js.org/
[1]: https://tuleap.net/plugins/tracker/?aid=40462
[2]: ../../scripts/graph-loader
[3]: https://tuleap.net/plugins/tracker/?aid=9463
[4]: https://www.chartjs.org/
[5]: https://gerrit.tuleap.net/c/tuleap/+/6571

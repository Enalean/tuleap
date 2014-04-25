/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

// Inspired from  http://bl.ocks.org/mbostock/3887051
tuleap.graphontrackersv5.draw.groupedbar = function (id, graph) {
    var margin = {top: 20, right: 20, bottom: 40, left: 40},
    width = graph.width - margin.left - margin.right,
    height = graph.height - margin.top - margin.bottom,
    d3_colors  = d3.scale.category20();

    // Fix a d3 color when the backend doesn't define one
    graph.colors.forEach(function (legend_color) {
        if (legend_color.color === null) {
            graph.colors[legend_color.name] = d3_colors(legend_color.name);
        }
    });

    var x = d3.scale.ordinal()
        .rangeRoundBands([0, width], .35);

    var xGrouped = d3.scale.ordinal();

    var y = d3.scale.linear()
        .range([height, 0]);

    var xAxis = d3.svg.axis()
        .scale(x)
        .orient("bottom");

    var yAxis = d3.svg.axis()
        .scale(y)
        .ticks(5)
        .tickSize(width)
        .orient("right");

    var svg = d3.selectAll(".plugin_graphontrackersv5_chart[data-graph-id="+id+']').append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom);

    var chart = svg.append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    x.domain(graph.values.map(function (d) { return d.name; }));
    xGrouped.domain(graph.grouped_labels).rangeRoundBands([0, x.rangeBand()]);
    y.domain(
        [
            0,
            d3.max(
                graph.values,
                function(d) {
                    return d3.max(
                        d3.values(d.values).map(
                            function (d) { return parseFloat(d.value); }
                        )
                    )
                }
            )
        ]
    );

    tuleap.graphontrackersv5.alternateXAxisLabels(chart, height, xAxis);

    var gy = chart.append("g")
        .attr("class", "y axis")
        .call(yAxis);

    // Set the label on the left of the y axis
    gy.selectAll('text')
        .attr("x", -30)
        .attr("dx", ".71em");

    var bar = chart.selectAll(".bar")
        .data(graph.values).enter().append("g")
      .attr("class", "g")
      .attr("transform", function(d) { return "translate(" + x(d.name) + ",0)"; });

    var grouped_bar = bar.selectAll("rect")
        .data(function(d) { return d.values; })
      .enter();

    grouped_bar.append("rect")
        .style("fill", function(d, i) { return graph.colors[d.name]; })
        .attr("class", "bar")
        .attr("width", xGrouped.rangeBand())
        .attr("x", function(d) { return xGrouped(d.name); })
        .attr("y", function(d) { return y(d.value); })
        .attr("height", function(d) { return height - y(d.value); })
        .attr('rx', 3)
        .attr('ry', 3);

    grouped_bar.append("text")
        .attr("x", function(d) { return xGrouped(d.name) + (xGrouped.rangeBand() / 2); })
        .attr("y", function(d) { return y(d.value) - 10; })
        .attr("dy", ".35em")
        .attr("text-anchor", "middle")
        .text(function(d) { return d.value; });
};
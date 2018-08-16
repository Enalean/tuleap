/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

var tuleap = tuleap || {};
tuleap.graphontrackersv5 = tuleap.graphontrackersv5 || {};
tuleap.graphontrackersv5.graphs = tuleap.graphontrackersv5.graphs || {};
tuleap.graphontrackersv5.draw = {};

tuleap.graphontrackersv5.topRoundedRect = function(x, y, width, height, radius) {
    return (
        "M" +
        (x + radius) +
        "," +
        y +
        "h" +
        (width - 2 * radius) +
        "a" +
        radius +
        "," +
        radius +
        " 0 0 1 " +
        radius +
        "," +
        radius +
        "v" +
        (height - radius) +
        "h" +
        -width +
        "v" +
        (-height + radius) +
        "a" +
        radius +
        "," +
        radius +
        " 0 0 1 " +
        radius +
        "," +
        -radius +
        "z"
    );
};

/**
 * Utility function to display x axis legend labels with alternate height so
 * we can read the labels as they no longer overlap
 *
 * Not inspired by bl.ocks \o/
 *
 * @param {Object} svg
 * @param {int} height
 * @param {Object} xAxis
 */
tuleap.graphontrackersv5.alternateXAxisLabels = function(svg, height, xAxis, legend) {
    var x_labels_alternate_delta = 15;
    var x_labels = svg
        .append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + height + ")")
        .call(xAxis);

    x_labels.selectAll("text").attr("transform", function(d, i) {
        var y_delta = 0;
        if (i % 2 === 0) {
            y_delta += x_labels_alternate_delta;
        }
        return "translate(0," + y_delta + ")";
    });

    if (typeof legend !== "undefined") {
        x_labels.selectAll("text").text(function(d, i) {
            return legend[i];
        });
    }

    x_labels
        .selectAll("line")
        .attr("x2", 0)
        .attr("y2", function(d, i) {
            var y_delta = 6;
            if (i % 2 === 0) {
                y_delta += x_labels_alternate_delta;
            }
            return y_delta;
        });
};

/**
 * Add a legend on the right of the chart
 *
 * @param {type} svg
 * @param {type} graph
 * @param {type} margin
 * @param {type} legend_width
 * @param {type} colors
 * @param {type} onOverValue
 * @param {type} onOutValue
 * @param {type} getLegendClass
 */
tuleap.graphontrackersv5.addLegendBox = function(
    svg,
    graph,
    margin,
    legend_width,
    colors,
    onOverValue,
    onOutValue,
    getLegendClass
) {
    var legend_x = graph.width - legend_width - margin.right,
        legend_y = Math.max(0, graph.height / 2 - (20 / 2) * colors.length),
        legend_group;

    legend_group = svg
        .append("g")
        .attr("transform", "translate(" + legend_x + ", " + legend_y + ")");

    var legend = legend_group
        .selectAll(".legend")
        .data(colors)
        .enter()
        .append("g")
        .attr("class", "legend")
        .attr("transform", function(d, i) {
            return "translate(0, " + i * 20 + ")";
        })
        .on("mouseover", onOverValue)
        .on("mouseout", onOutValue);

    legend
        .append("rect")
        .attr("x", 0)
        .attr("rx", 3)
        .attr("ry", 3)
        .attr("width", 16)
        .attr("height", 16)
        .attr("class", function(d) {
            if (d.color.indexOf("#") > -1) {
                return;
            }

            return "graph-element-" + d.color;
        })
        .style("fill", function(d) {
            return d.color;
        });

    legend
        .append("text")
        .attr("class", function(d, i) {
            return getLegendClass(i);
        })
        .attr("x", 22)
        .attr("y", 8)
        .attr("dy", ".35em")
        .style("text-anchor", "start")
        .text(function(d) {
            var legend = d.label,
                length = legend.length;

            if (length > 25) {
                return legend.substr(0, 15) + "…" + legend.substr(length - 10, length);
            }
            return legend;
        });
};

/**
 * Add gradients for given colors in svg definitions
 *
 * @param {type} svg
 * @param {type} colors
 * @param {type} getGradientId
 */
tuleap.graphontrackersv5.defineGradients = function(svg, colors, getGradientId) {
    var grads = svg
        .append("defs")
        .selectAll("linearGradient")
        .data(colors)
        .enter()
        .append("linearGradient")
        .attr("x1", 0)
        .attr("y1", 0)
        .attr("x2", 0)
        .attr("y2", 1)
        .attr("id", function(d, i) {
            return getGradientId(i);
        });
    grads
        .append("stop")
        .attr("offset", "0%")
        .style("stop-color", function(d, i) {
            return d3.rgb(d.color).brighter(0.5);
        });
    grads
        .append("stop")
        .attr("offset", "100%")
        .style("stop-color", function(d, i) {
            return d.color;
        });
};

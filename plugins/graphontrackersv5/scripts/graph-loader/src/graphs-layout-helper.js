/*
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

import { rgb } from "d3-color";

export { topRoundedRect, alternateXAxisLabels, addLegendBox, defineGradients };

function topRoundedRect(x, y, width, height, radius) {
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
}

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
function alternateXAxisLabels(svg, height, xAxis, legend) {
    const x_labels_alternate_delta = 15;
    const x_labels = svg
        .append("g")
        .attr("class", "x axis")
        .attr("transform", "translate(0," + height + ")")
        .call(xAxis);

    x_labels.selectAll("text").attr("transform", (d, i) => {
        let y_delta = 0;
        if (i % 2 === 0) {
            y_delta += x_labels_alternate_delta;
        }
        return "translate(0," + y_delta + ")";
    });

    if (typeof legend !== "undefined") {
        x_labels.selectAll("text").text((d, i) => legend[i]);
    }

    x_labels
        .selectAll("line")
        .attr("x2", 0)
        .attr("y2", (d, i) => {
            let y_delta = 6;
            if (i % 2 === 0) {
                y_delta += x_labels_alternate_delta;
            }
            return y_delta;
        });
}

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
function addLegendBox(
    svg,
    { width, height },
    { right },
    legend_width,
    colors,
    onOverValue,
    onOutValue,
    getLegendClass,
) {
    const legend_x = width - legend_width - right;
    const legend_y = Math.max(0, height / 2 - (20 / 2) * colors.length);
    let legend_group;

    legend_group = svg
        .append("g")
        .attr("transform", "translate(" + legend_x + ", " + legend_y + ")");

    const legend = legend_group
        .selectAll(".legend")
        .data(colors)
        .enter()
        .append("g")
        .attr("class", "legend")
        .attr("transform", (d, i) => "translate(0, " + i * 20 + ")")
        .on("mouseover", function (event) {
            const nodes = legend.nodes();
            const index = nodes.indexOf(event.currentTarget);
            return onOverValue(index);
        })
        .on("mouseout", function (event) {
            const nodes = legend.nodes();
            const index = nodes.indexOf(event.currentTarget);
            return onOutValue(index);
        });

    legend
        .append("rect")
        .attr("x", 0)
        .attr("rx", 3)
        .attr("ry", 3)
        .attr("width", 16)
        .attr("height", 16)
        .attr("class", ({ color }) => {
            if (color.includes("#")) {
                return "";
            }

            return "graph-element-" + color;
        })
        .style("fill", ({ color }) => color);

    legend
        .append("text")
        .attr("class", (d, i) => getLegendClass(i))
        .attr("x", 22)
        .attr("y", 8)
        .attr("dy", ".35em")
        .style("text-anchor", "start")
        .text(({ label }) => {
            const legend = label,
                length = legend.length;

            if (length > 25) {
                return legend.substr(0, 15) + "…" + legend.substr(length - 10, length);
            }
            return legend;
        });
}

/**
 * Add gradients for given colors in svg definitions
 *
 * @param {type} svg
 * @param {type} colors
 * @param {type} getGradientId
 */
function defineGradients(svg, colors, getGradientId) {
    const grads = svg
        .append("defs")
        .selectAll("linearGradient")
        .data(colors)
        .enter()
        .append("linearGradient")
        .attr("x1", 0)
        .attr("y1", 0)
        .attr("x2", 0)
        .attr("y2", 1)
        .attr("id", (d, i) => getGradientId(i));
    grads
        .append("stop")
        .attr("offset", "0%")
        .style("stop-color", ({ color }) => rgb(color).brighter(0.5));
    grads
        .append("stop")
        .attr("offset", "100%")
        .style("stop-color", ({ color }) => color);
}

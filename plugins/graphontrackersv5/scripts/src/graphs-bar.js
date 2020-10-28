/*
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

import { topRoundedRect, alternateXAxisLabels, defineGradients } from "./graphs-layout-helper.js";
import { interpolateNumber } from "d3-interpolate";
import { schemeCategory10 } from "d3-scale-chromatic";
import { scaleBand, scaleLinear } from "d3-scale";
import { axisBottom, axisRight } from "d3-axis";
import { selectAll } from "d3-selection";

// Inspired from  http://bl.ocks.org/mbostock/3887051
export function bar(id, graph) {
    const margin = { top: 20, right: 20, bottom: 40, left: 40 },
        width = graph.width - margin.left - margin.right,
        height = graph.height - margin.top - margin.bottom,
        color = schemeCategory10;

    const x = scaleBand().rangeRound([0, width], 0.35);
    const y = scaleLinear().range([height, 0]);

    const xAxis = axisBottom(x);
    const yAxis = axisRight(y).ticks(5).tickSize(width);

    const svg = selectAll('.plugin_graphontrackersv5_chart[data-graph-id="' + id + '"]')
        .append("svg")
        .attr("width", width + margin.left + margin.right)
        .attr("height", height + margin.top + margin.bottom)
        .append("g")
        .attr("transform", "translate(" + margin.left + "," + margin.top + ")");

    // Start with some data
    const data = [];
    for (let i = 0; i < graph.data.length; ++i) {
        let c = graph.colors[i];
        if (c === null) {
            c = color[i];
        }
        data.push({
            label: graph.legend[i],
            value: parseFloat(graph.data[i]),
            color: c,
        });
    }

    defineGradients(svg, data, getGradientId);

    const max_y_value_object = data.reduce((p, c) => (p.value > c.value ? p : c));
    x.domain(data.map((d, i) => i));
    y.domain([0, max_y_value_object.value]);

    alternateXAxisLabels(svg, height, xAxis, graph.legend);

    const gy = svg.append("g").attr("class", "y axis").call(yAxis);

    // Set the label on the left of the y axis
    gy.selectAll("text").attr("x", -30).attr("dx", ".71em");

    const bar = svg.selectAll(".bar").data(data).enter();

    bar.append("path")
        .style("fill", (d, i) => {
            if (!isHexaColor(d.color)) {
                return "";
            }

            return "url(#" + getGradientId(i) + ")";
        })
        .attr("class", (d) => {
            if (!isHexaColor(d.color)) {
                return "bar graph-element-" + d.color;
            }

            return "bar";
        })
        .transition()
        .duration(750)
        .attrTween("d", ({ value }, j) => {
            const i = interpolateNumber(height, y(value));

            return (t) =>
                topRoundedRect(x(j) + x.bandwidth() / 4, i(t), x.bandwidth() / 2, height - i(t), 3);
        });

    bar.append("text")
        .attr("x", (d, i) => x(i) + x.bandwidth() / 2)
        .attr("y", () => height)
        .attr("dy", ".35em")
        .attr("text-anchor", "middle")
        .text(({ value }) => value)
        .transition()
        .duration(750)
        .attr("y", ({ value }) => y(value) - 10);

    function getGradientId(value_index) {
        return "grad_" + id + "_" + value_index;
    }

    function isHexaColor(color) {
        return color.includes("#");
    }
}

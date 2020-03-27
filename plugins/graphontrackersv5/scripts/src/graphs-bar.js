/*
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

import * as d3 from "d3";

import { topRoundedRect, alternateXAxisLabels, defineGradients } from "./graphs-layout-helper.js";

// Inspired from  http://bl.ocks.org/mbostock/3887051
export function bar(id, graph) {
    const margin = { top: 20, right: 20, bottom: 40, left: 40 },
        width = graph.width - margin.left - margin.right,
        height = graph.height - margin.top - margin.bottom,
        color = d3.scale.category20();

    const x = d3.scale.ordinal().rangeRoundBands([0, width], 0.35);

    const y = d3.scale.linear().range([height, 0]);

    const xAxis = d3.svg.axis().scale(x).orient("bottom");

    const yAxis = d3.svg.axis().scale(y).ticks(5).tickSize(width).orient("right");

    const svg = d3
        .selectAll('.plugin_graphontrackersv5_chart[data-graph-id="' + id + '"]')
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
            c = color(i);
        }
        data.push({
            label: graph.legend[i],
            value: parseFloat(graph.data[i]),
            color: c,
        });
    }

    defineGradients(svg, data, getGradientId);

    x.domain(data.map((d, i) => i));
    y.domain([0, d3.max(data, ({ value }) => value)]);

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
            const i = d3.interpolateNumber(height, y(value));

            return (t) => topRoundedRect(x(j), i(t), x.rangeBand(), height - i(t), 3);
        });

    bar.append("text")
        .attr("x", (d, i) => x(i) + x.rangeBand() / 2)
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

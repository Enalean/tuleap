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

import {
    topRoundedRect,
    alternateXAxisLabels,
    addLegendBox,
    defineGradients,
} from "./graphs-layout-helper.js";

// Inspired from  http://bl.ocks.org/mbostock/3887051
export function groupedbar(id, graph) {
    const margin = { top: 20, right: 20, bottom: 20, left: 20 },
        axis_margin = { bottom: 20, left: 20 },
        width = graph.width - margin.left - margin.right - axis_margin.left,
        height = graph.height - margin.top - margin.bottom - axis_margin.bottom,
        d3_colors = d3.scale.category20(),
        legend_width = 170,
        legend_margin = 20,
        margin_left = margin.left + axis_margin.left,
        chart_width = width - legend_width - legend_margin;

    // Fix a d3 color when the backend doesn't define one
    graph.colors.forEach(({ color }, i) => {
        if (color === null) {
            graph.colors[i].color = d3_colors(i);
        } else {
            graph.colors[i].color = color;
        }
    });

    const x = d3.scale.ordinal().rangeRoundBands([0, chart_width], 0.35);

    const xGrouped = d3.scale.ordinal();

    const y = d3.scale.linear().range([height, 0]);

    const xAxis = d3.svg.axis().scale(x).orient("bottom");

    const yAxis = d3.svg.axis().scale(y).ticks(5).tickSize(chart_width).orient("right");
    const svg = d3
        .selectAll('.plugin_graphontrackersv5_chart[data-graph-id="' + id + '"]')
        .append("svg")
        .attr("width", graph.width)
        .attr("height", graph.height);

    defineGradients(svg, graph.colors, getGradientId);

    const chart = svg
        .append("g")
        .attr("transform", "translate(" + margin_left + "," + margin.top + ")");

    const xAxisLabels = graph.values.map(({ label }) => label);

    x.domain(graph.values.map((d, i) => i));
    xGrouped.domain(graph.grouped_labels.map((d, i) => i)).rangeRoundBands([0, x.rangeBand()]);
    y.domain([
        0,
        d3.max(graph.values, ({ values }) =>
            d3.max(d3.values(values).map(({ value }) => parseFloat(value)))
        ),
    ]);

    alternateXAxisLabels(chart, height, xAxis, xAxisLabels);

    const gy = chart.append("g").attr("class", "y axis").call(yAxis);

    // Set the label on the left of the y axis
    gy.selectAll("text").attr("x", -30).attr("dx", ".71em");

    const bar = chart
        .selectAll(".bar")
        .data(graph.values)
        .enter()
        .append("g")
        .attr("class", "g")
        .attr("transform", (d, i) => "translate(" + x(i) + ",0)");

    const grouped_bar = bar
        .selectAll("path")
        .data(({ values }) => values)
        .enter()
        .append("g")
        .on("mouseover", onOverValue)
        .on("mouseout", onOutValue);

    grouped_bar
        .append("path")
        .style("fill", (d, i) => {
            const color = getColor(i);

            if (!isHexaColor(color)) {
                return "";
            }

            return "url(#" + getGradientId(i) + ")";
        })
        .attr("class", (d, i) => {
            const color = getColor(i);

            if (!isHexaColor(color)) {
                return "bar graph-element-" + color;
            }

            return "bar";
        })
        .transition()
        .duration(750)
        .attrTween("d", ({ value }, i) => {
            const interpolate = d3.interpolateNumber(height, y(value));

            return (t) =>
                topRoundedRect(
                    xGrouped(i),
                    interpolate(t),
                    xGrouped.rangeBand(),
                    height - interpolate(t),
                    3
                );
        });

    grouped_bar
        .append("text")
        .attr("class", (d, i) => getTextClass(i))
        .attr("x", (d, i) => xGrouped(i) + xGrouped.rangeBand() / 2)
        .attr("y", () => height)
        .attr("dy", ".35em")
        .attr("text-anchor", "middle")
        .text(({ value }) => value)
        .transition()
        .duration(750)
        .attr("y", ({ value }) => y(value) - 10);

    addLegendBox(
        svg,
        graph,
        margin,
        legend_width,
        graph.colors,
        onOverValue,
        onOutValue,
        getLegendClass
    );

    function getGradientId(value_index) {
        return "grad_" + id + "_" + value_index;
    }

    function getLegendClass(value_index) {
        return "legend_" + id + "_" + value_index;
    }

    function getTextClass(value_index) {
        return "text_" + id + "_" + value_index;
    }

    function onOverValue(d, index) {
        svg.selectAll("." + getTextClass(index)).style("font-weight", "bold");
        svg.select("." + getLegendClass(index)).style("font-weight", "bold");
    }

    function onOutValue(d, index) {
        svg.selectAll("." + getTextClass(index)).style("font-weight", "normal");
        svg.select("." + getLegendClass(index)).style("font-weight", "normal");
    }

    function isHexaColor(color) {
        return color.includes("#");
    }

    function getColor(index) {
        const color = graph.colors[index];

        return color ? color.color : null;
    }
}

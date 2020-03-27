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

import { addLegendBox, defineGradients } from "./graphs-layout-helper.js";

// Inspired from http://bl.ocks.org/mbostock/3887193
export function pie(id, graph) {
    const margin = { top: 0, right: 0, bottom: 0, left: 0 };
    const width = graph.width;
    const height = graph.height;
    const radius = Math.min(width, height) / 2;

    const data = [];
    let total_values = 0;
    const color = d3.scale.category20c();

    for (let i = 0; i < graph.data.length; ++i) {
        total_values += parseFloat(graph.data[i]);
    }

    for (let i = 0; i < graph.data.length; ++i) {
        let c = graph.colors[i];
        if (c === null) {
            c = color(i);
        }
        const value = parseFloat(graph.data[i]);
        const line = {
            label: graph.legend[i],
            value,
            percentage: ((value / total_values) * 100).toFixed(0),
            color: c,
        };

        data.push(line);
        graph.colors[i] = c;
    }

    const pie = d3.layout
        .pie()
        .value(({ value }) => value)
        .sort(null);

    const arc = d3.svg
        .arc()
        .innerRadius((radius - 50) / 2)
        .outerRadius(radius - 50);

    const svg = d3
        .selectAll('.plugin_graphontrackersv5_chart[data-graph-id="' + id + '"]')
        .append("svg")
        .attr("width", width)
        .attr("height", height);

    defineGradients(svg, data, getGradientId);

    const chart = svg
        .append("g")
        .attr("transform", "translate(" + width / 3 + "," + height / 2 + ")");

    drawDonutChart();

    function drawDonutChart() {
        const slice = chart
            .selectAll(".arc")
            .data(pie(data))
            .enter()
            .append("g")
            .attr("class", "arc");

        drawDonutSlice(slice);
    }

    function drawDonutSlice(slice) {
        drawTick(slice);

        slice
            .append("path")
            .attr("d", arc)
            .style("fill", (d, i) => {
                if (!isHexaColor(d.data.color)) {
                    return "";
                }

                return "url(#" + getGradientId(i) + ")";
            })
            .attr("class", (d, i) => {
                if (isHexaColor(d.data.color)) {
                    return getDonutSliceClass(i);
                }

                return getDonutSliceClass(i) + " graph-element-" + d.data.color;
            })
            .on("mouseover", onOverValue)
            .on("mouseout", onOutValue)
            .transition()
            .duration(750)
            .attrTween("d", (b) => {
                const i = d3.interpolate(
                    {
                        startAngle: 0,
                        endAngle: 0,
                    },
                    b
                );

                return (t) => arc(i(t));
            });
    }

    function drawTick(slice) {
        slice
            .append("line")
            .attr("x1", 0)
            .attr("x2", 0)
            .attr("y1", -radius + 50)
            .attr("y2", (d, i) => {
                if (i % 2 === 0) {
                    return -radius + 40;
                }
                return -radius + 25;
            })
            .attr("stroke", "#DDD")
            .attr("transform", ({ startAngle, endAngle }) => {
                radius;
                return "rotate(" + ((startAngle + endAngle) / 2) * (180 / Math.PI) + ")";
            });

        slice
            .append("text")
            .attr("transform", (d) => "translate(" + arc.centroid(d) + ")")
            .attr("transform", ({ startAngle, endAngle }, i) => {
                let dist;
                if (i % 2 === 0) {
                    dist = radius - 34;
                } else {
                    dist = radius - 19;
                }

                // Middle of wedge
                const angle = (startAngle + endAngle) / 2,
                    x = dist * Math.sin(angle),
                    y = -dist * Math.cos(angle);

                return "translate(" + x + "," + y + ")";
            })
            .attr("dy", ".35em")
            .style("text-anchor", ({ startAngle, endAngle }) => {
                const angle = (startAngle + endAngle) / 2;

                if (angle > Math.PI) {
                    return "end";
                }
                return "start";
            })
            .text((d) => d.data.percentage + "%");
    }

    addLegendBox(svg, graph, margin, width / 3, data, onOverValue, onOutValue, getLegendClass);

    function onOverValue(d, index) {
        svg.select("." + getDonutSliceClass(index))
            .transition()
            .attr("transform", "scale(1.05)");
        svg.select("." + getLegendClass(index)).style("font-weight", "bold");
    }

    function onOutValue(d, index) {
        svg.select("." + getDonutSliceClass(index))
            .transition()
            .attr("transform", "scale(1)");
        svg.select("." + getLegendClass(index)).style("font-weight", "normal");
    }

    function getGradientId(value_index) {
        return "grad_" + id + "_" + value_index;
    }

    function getLegendClass(value_index) {
        return "legend_" + id + "_" + value_index;
    }

    function getDonutSliceClass(value_index) {
        return "slice_" + id + "_" + value_index;
    }

    function isHexaColor(color) {
        return color && color.includes("#");
    }
}

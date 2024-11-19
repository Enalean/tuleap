/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import { selectAll } from "d3-selection";
import { scaleOrdinal, scaleLinear, scaleTime } from "d3-scale";
import { stack, area, stackOrderNone, stackOffsetNone } from "d3-shape";
import { axisBottom, axisLeft } from "d3-axis";

export function cumulativeflow(id, graph) {
    if (graph.error) {
        return showError();
    }

    const schemeCategory20cWithoutLightest = [
        "#3182bd",
        "#6baed6",
        "#9ecae1",
        "#e6550d",
        "#fd8d3c",
        "#fdae6b",
        "#31a354",
        "#74c476",
        "#a1d99b",
        "#756bb1",
        "#9e9ac8",
        "#bcbddc",
        "#636363",
        "#969696",
        "#bdbdbd",
    ];

    const margin = { top: 20, right: 20, bottom: 60, left: 40 },
        width = graph.width - margin.left - margin.right,
        height = graph.height - margin.top - margin.bottom,
        color = scaleOrdinal().range(schemeCategory20cWithoutLightest),
        locale = document.body.dataset.userLocale.replace(/_/gi, "-");

    const MAX_X_TICKS = 15;

    const svg = initSVG();
    const legend = initLegend();
    const { x_scale, x_axis } = initX();
    const { y_scale, y_axis } = initY();
    const stacks = initStacks();
    const areas = initAreas();

    drawAxis();
    drawAreas();
    drawLegend();
    rotateAxisLabels();

    function drawAreas() {
        svg.selectAll(".area")
            .data(stacks(parseData()))
            .enter()
            .append("path")
            .attr("id", (d) => `area_${d.key}`)
            .attr("class", (d, i) => {
                const column_color = getColumnColor(d) || color(i);

                if (column_color.includes("#")) {
                    return "area";
                }

                return `area graph-element-${column_color}`;
            })
            .attr("fill", (d, i) => {
                const column_color = getColumnColor(d) || color(i);

                if (column_color.includes("#")) {
                    return column_color;
                }

                return "";
            })
            .attr("d", areas);
    }

    function drawAxis() {
        svg.append("g")
            .attr("class", "axis x-axis")
            .attr("transform", `translate(0, ${height - margin.bottom})`)
            .call(x_axis);

        svg.append("g").attr("class", "axis y-axis").call(y_axis);

        svg.selectAll(".y-axis .tick:not(:first-of-type) line")
            .attr("stroke", "#777")
            .attr("stroke-dasharray", "2,2");
    }

    function drawLegend() {
        const items = legend
            .selectAll("li")
            .data(graph.data)
            .enter()
            .append("li")
            .attr("class", "cumulative-flowchart-legend-list-item");

        items
            .append("span")
            .attr("class", (d) => {
                const legend_class = "cumulative-flowchart-legend-color";

                if (!d.color || d.color.includes("#")) {
                    return legend_class;
                }

                return `${legend_class} cumulative-flowchart-legend-color-${d.color}`;
            })
            .attr("style", (d, i) => {
                let legend_color = d.color || color(i);

                if (legend_color.includes("#")) {
                    return `background-color: ${legend_color}`;
                }

                return "";
            });

        items.append("span").text((d) => d.label);
    }

    function initAreas() {
        return area()
            .x((d) => x_scale(new Date(d.data.date * 1000)))
            .y0((d) => y_scale(d[0]))
            .y1((d) => y_scale(d[1]));
    }

    function initStacks() {
        const keys = graph.data.map((column) => column.id);

        return stack().keys(keys).order(stackOrderNone).offset(stackOffsetNone);
    }

    function initLegend() {
        return selectAll(`.plugin_graphontrackersv5_chart[data-graph-id="${id}"]`)
            .append("div")
            .attr("width", width + margin.left + margin.right)
            .attr("class", "cumulative-flowchart-legend")
            .append("ul")
            .attr("class", "cumulative-flowchart-legend-list");
    }

    function initSVG() {
        return selectAll(`.plugin_graphontrackersv5_chart[data-graph-id="${id}"]`)
            .append("svg")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height)
            .append("g")
            .attr("transform", `translate(${margin.left}, ${margin.top})`);
    }

    function initX() {
        const { extent, ticks } = getTimeFrame();

        const x_scale = scaleTime().domain(extent).range([0, width]);

        const x_axis = axisBottom()
            .scale(x_scale)
            .tickFormat((date) =>
                date.toLocaleDateString(locale, {
                    month: "2-digit",
                    day: "2-digit",
                    year: "2-digit",
                }),
            )
            .tickValues(ticks);

        return { x_scale, x_axis };
    }

    function initY() {
        const max_y = getMaxY();

        const y_scale = scaleLinear()
            .domain([0, max_y])
            .range([height - margin.bottom, 0]);

        const y_axis = axisLeft().scale(y_scale).tickSize(-width);
        return { y_scale, y_axis };
    }

    function getColumnColor(d) {
        return graph.data.find((column) => column.id === d.key).color;
    }

    function getMaxY() {
        const first_column = graph.data[0];

        let y_max = 0;
        first_column.values.forEach((data_point, index) => {
            const day_sum = getDaySum(index);

            if (day_sum > y_max) {
                y_max = day_sum;
            }
        });

        return y_max;

        function getDaySum(day_index) {
            return graph.data.reduce(
                (previous_sum, current_column) =>
                    previous_sum + current_column.values[day_index].count,
                0,
            );
        }
    }

    function getTimeFrame() {
        const first_column_values = graph.data[0].values;
        const all_dates = first_column_values.map((value) => new Date(value.date * 1000));
        const ticks = getTicksToDisplayAccordingToGranularity(all_dates);

        return {
            extent: [
                new Date(first_column_values[0].date * 1000),
                new Date(first_column_values[first_column_values.length - 1].date * 1000),
            ],
            ticks,
        };
    }

    function getTicksToDisplayAccordingToGranularity(dates) {
        if (dates.length < MAX_X_TICKS) {
            return dates;
        }

        const divisor = Math.round(dates.length / MAX_X_TICKS);

        return dates.filter((date, index) => index % divisor === 0);
    }

    function parseData() {
        const parsed_data = [];
        graph.data.forEach((column) => {
            column.values.forEach((value, value_index) => {
                if (!parsed_data[value_index]) {
                    parsed_data[value_index] = {};
                }

                parsed_data[value_index][column.id] = value.count;
                parsed_data[value_index].date = value.date;
            });
        });

        return parsed_data;
    }

    function showError() {
        const alert = selectAll(`.plugin_graphontrackersv5_chart[data-graph-id="${id}"]`)
            .append("div")
            .attr("class", "alert alert-error");

        alert.append("h3").text(graph.error.message);
        alert.append("p").text(graph.error.cause);
    }

    function rotateAxisLabels() {
        svg.selectAll(".x-axis .tick > text").attr("transform", "rotate(-45), translate(-20, 0)");
    }
}

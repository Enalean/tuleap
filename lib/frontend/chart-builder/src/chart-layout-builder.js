/**
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

import { select } from "d3-selection";
import { axisLeft, axisBottom } from "d3-axis";
import { getYAxisTicksSize } from "./chart-layout-service.js";

export { buildChartLayout };

const DEFAULT_NB_TICKS = 10,
    DEFAULT_TICK_PADDING = 20;

function buildChartLayout(
    chart_container,
    { graph_width, graph_height, margins },
    scales,
    nb_ticks = DEFAULT_NB_TICKS,
    tick_padding = DEFAULT_TICK_PADDING,
) {
    const layout = drawSVG(chart_container, graph_width, graph_height);
    const axes = initAxis(graph_width, margins, scales, nb_ticks, tick_padding);

    drawAxis(layout, axes, graph_height, margins);

    return layout;
}

function drawSVG(element, width, height) {
    return select(element).append("svg").attr("width", width).attr("height", height);
}

function initAxis(graph_width, margins, { x_scale, y_scale }, nb_ticks, tick_padding) {
    const y_ticks_size = getYAxisTicksSize(graph_width, margins.right, margins.left);

    return {
        x_axis: initXAxis(x_scale, tick_padding),
        y_axis: initYAxis(y_scale, y_ticks_size, nb_ticks, tick_padding),
    };
}

function initXAxis(x_scale, tick_padding) {
    return axisBottom(x_scale).tickPadding(tick_padding);
}

function initYAxis(y_scale, y_ticks_size, nb_ticks, tick_padding) {
    return axisLeft(y_scale).ticks(nb_ticks).tickSize(-y_ticks_size).tickPadding(tick_padding);
}

function drawAxis(layout, { x_axis, y_axis }, graph_height, { left, bottom }) {
    const y_position = graph_height - bottom;

    layout
        .append("g")
        .attr("class", "chart-x-axis")
        .attr("transform", `translate(0, ${y_position})`)
        .call(x_axis);

    layout
        .append("g")
        .attr("class", "chart-y-axis")
        .attr("transform", `translate(${left}, 0)`)
        .call(y_axis);

    layout.selectAll(".domain").remove();
    layout.selectAll(".chart-x-axis > .tick > line").remove();
}

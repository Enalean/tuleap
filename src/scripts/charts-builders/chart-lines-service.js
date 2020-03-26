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

import { line } from "d3-shape";
import { extent } from "d3-array";

export { drawIdealLine, drawCurve };

function drawIdealLine(container, { x_scale, y_scale }, { line_start, line_end }) {
    const [x_minimum, x_maximum] = extent(x_scale.domain());

    const coordinates = [
        {
            x_coordinate: x_scale(x_minimum),
            y_coordinate: y_scale(line_start),
        },
        {
            x_coordinate: x_scale(x_maximum),
            y_coordinate: y_scale(line_end),
        },
    ];

    const ideal_line = container.append("g").attr("class", "ideal-line");

    const ideal_line_generator = line()
        .x(({ x_coordinate }) => x_coordinate)
        .y(({ y_coordinate }) => y_coordinate);

    ideal_line
        .selectAll("circle")
        .data(coordinates)
        .enter()
        .append("circle")
        .attr("class", "circle")
        .attr("cx", ({ x_coordinate }) => x_coordinate)
        .attr("cy", ({ y_coordinate }) => y_coordinate)
        .attr("r", 4);

    ideal_line.append("path").datum(coordinates).attr("d", ideal_line_generator);
}

function drawCurve(container, { x_scale, y_scale }, dataset, line_name) {
    const lines = line()
        .x(({ date }) => x_scale(date))
        .y((point) => y_scale(point[line_name]));

    const class_name = line_name.replace("_", "-");
    container
        .append("path")
        .datum(dataset)
        .attr("class", `chart-curve-${class_name}`)
        .attr("d", lines);
}

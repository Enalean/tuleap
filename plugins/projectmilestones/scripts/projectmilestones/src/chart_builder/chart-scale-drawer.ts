/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { Selection } from "d3-selection";
import type { XYMinMaxCoordinates } from "../type";

const OVERSIZE_LINE_SCALE = 10;

export function addScaleLines(
    svg_burndown: Selection<SVGSVGElement, unknown, null, undefined>,
    coordinates: XYMinMaxCoordinates,
): void {
    svg_burndown
        .append("line")
        .attr("class", "release-line-scale")
        .attr("x1", coordinates.x_coordinate_minimum)
        .attr("y1", coordinates.y_coordinate_minimum)
        .attr("x2", coordinates.x_coordinate_maximum + OVERSIZE_LINE_SCALE)
        .attr("y2", coordinates.y_coordinate_minimum);

    svg_burndown
        .append("line")
        .attr("class", "release-line-scale")
        .attr("x1", coordinates.x_coordinate_minimum)
        .attr("y1", coordinates.y_coordinate_minimum)
        .attr("x2", coordinates.x_coordinate_minimum)
        .attr("y2", coordinates.y_coordinate_maximum - OVERSIZE_LINE_SCALE);
}

/*
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import { scaleLinear, scaleBand } from "d3-scale";

export { buildBarChartScales };

function buildBarChartScales({
    margins,
    graph_width,
    graph_height,
    y_axis_maximum,
    x_axis_tick_values,
    bands_paddings,
}) {
    return {
        x_scale: initXScale(),
        y_scale: initYScale(),
    };

    function initXScale() {
        return scaleBand()
            .domain(x_axis_tick_values)
            .range([margins.left, graph_width - margins.right])
            .padding(bands_paddings);
    }

    function initYScale() {
        return scaleLinear()
            .domain([0, y_axis_maximum])
            .range([graph_height - margins.bottom, margins.top])
            .nice()
            .clamp(true);
    }
}

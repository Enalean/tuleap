/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 *
 */

import {
    ChartPropsWhithoutTooltip,
    XYScale
} from "../../../../../../../src/www/scripts/charts-builders/type";
import { GenericBurnupData } from "../../../../../../agiledashboard/scripts/burnup-chart/src/type";
import { getDaysToDisplay } from "../../../../../../../src/www/scripts/charts-builders/chart-dates-service";
import { max } from "d3-array";
import { buildGraphScales } from "../../../../../../../src/www/scripts/charts-builders/line-chart-scales-factory";
import { select } from "d3-selection";
import { drawIdealLine } from "../../../../../../../src/www/scripts/charts-builders/chart-lines-service";
import { getLastGenericBurnupData } from "../chart-data-service";
import { addScaleLines } from "../chart-scale-drawer";
import { getCoordinatesScaleLines } from "../chart-scale-helper";

export { createBurnupChart, getTotal };

const DEFAULT_TOTAL_EFFORT = 5;

function createBurnupChart(
    chart_container: HTMLElement,
    chart_props: ChartPropsWhithoutTooltip,
    generic_burnup_data: GenericBurnupData
): void {
    const x_axis_tick_values = getDaysToDisplay(generic_burnup_data),
        total_effort = getTotal(generic_burnup_data);

    const properties = {
        ...chart_props,
        x_axis_tick_values,
        y_axis_maximum: total_effort
    };

    const { x_scale, y_scale }: XYScale = buildGraphScales(properties);

    const coordinates_scale_lines = getCoordinatesScaleLines({ x_scale, y_scale }, total_effort);

    const last_day_data = getLastGenericBurnupData(generic_burnup_data.points_with_date);

    drawBurnupChart();

    function drawBurnupChart(): void {
        if (!coordinates_scale_lines) {
            return;
        }

        const svg_burnup = select(chart_container)
            .append("svg")
            .attr("width", chart_props.graph_width)
            .attr("height", chart_props.graph_height);

        drawIdealLine(
            svg_burnup,
            { x_scale, y_scale },
            { line_start: 0, line_end: getLastDataTotal() }
        );
        select(chart_container)
            .selectAll("circle")
            .remove();

        addScaleLines(svg_burnup, coordinates_scale_lines);
    }

    function getLastDataTotal(): number {
        const total =
            last_day_data && last_day_data.total
                ? last_day_data.total
                : generic_burnup_data.capacity;
        if (!total) {
            return DEFAULT_TOTAL_EFFORT;
        }
        return total;
    }
}

function getTotal({ points_with_date, capacity }: GenericBurnupData): number {
    const max_total = max(points_with_date, ({ total }) => total);

    if (max_total) {
        return max_total;
    }

    if (capacity) {
        return capacity;
    }

    return DEFAULT_TOTAL_EFFORT;
}

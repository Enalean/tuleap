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

import { extent, max } from "d3-array";
import { select } from "d3-selection";
import { buildGraphScales } from "../../../../../src/www/scripts/charts-builders/line-chart-scales-factory";
import { getDaysToDisplay } from "../../../../../src/www/scripts/charts-builders/chart-dates-service";
import { BurndownData } from "../src/type";

import {
    ChartPropsBurndownWhithoutTooltip,
    PropertiesBuilderGraph,
    XYScale
} from "../../../../../src/www/scripts/charts-builders/type";
import { addScaleLines } from "./burndown-scale-drawer";
import { drawIdealLine } from "../../../../../src/www/scripts/charts-builders/chart-lines-service";
import { buildChartLayout } from "../../../../../src/www/scripts/charts-builders/chart-layout-builder";
import { TimeScaleLabelsFormatter } from "../../../../../src/www/scripts/charts-builders/time-scale-labels-formatter";
import { removeAllLabelsOverlapsOthersLabels } from "./burndown-time-scale-label-formatter";

const DEFAULT_REMAINING_EFFORT = 5;

export { createBurndownChart, getMaxRemainingEffort };

function createBurndownChart(
    chart_container: HTMLElement,
    chart_props: ChartPropsBurndownWhithoutTooltip,
    burndown_data: BurndownData
): void {
    if (!burndown_data.points_with_date.length) {
        return;
    }

    const x_axis_tick_values = getDaysToDisplay(burndown_data),
        y_axis_maximum = getMaxRemainingEffort(burndown_data);

    const properties: PropertiesBuilderGraph = {
        ...chart_props,
        x_axis_tick_values,
        y_axis_maximum
    };

    const { x_scale, y_scale }: XYScale = buildGraphScales(properties);

    const [x_minimum, x_maximum] = extent(x_scale.domain());

    if (!x_minimum || !x_maximum) {
        return;
    }

    const x_scale_minimum = x_scale(x_minimum);
    const x_scale_maximum = x_scale(x_maximum);

    if (!x_scale_minimum || !x_scale_maximum) {
        return;
    }

    const coordinates_scale_lines = {
        x_coordinate_minimum: x_scale_minimum,
        y_coordinate_minimum: y_scale(0),
        x_coordinate_maximum: x_scale_maximum,
        y_coordinate_maximum: y_scale(y_axis_maximum)
    };

    const first_ideal_line_point = burndown_data.capacity ? burndown_data.capacity : y_axis_maximum;

    const nb_ticks = 4,
        tick_padding = 5;

    drawBurndownChart();

    function drawBurndownChart(): void {
        const svg_burndown = buildChartLayout(
            chart_container,
            chart_props,
            { x_scale, y_scale },
            nb_ticks,
            tick_padding
        );

        drawIdealLine(
            svg_burndown,
            { x_scale, y_scale },
            { line_start: first_ideal_line_point, line_end: 0 }
        );
        select(chart_container)
            .selectAll("circle")
            .remove();
        select(chart_container)
            .selectAll(".chart-y-axis > .tick > line")
            .remove();

        addScaleLines(svg_burndown, coordinates_scale_lines);

        new TimeScaleLabelsFormatter({
            layout: svg_burndown,
            first_date: x_axis_tick_values[0],
            last_date: x_axis_tick_values[x_axis_tick_values.length - 1]
        }).formatTicks();

        removeAllLabelsOverlapsOthersLabels(svg_burndown);
    }
}

function getMaxRemainingEffort({ points_with_date, capacity }: BurndownData): number {
    const max_remaining_effort = max(
        points_with_date,
        ({ remaining_effort }: { remaining_effort: number | null }) => remaining_effort
    );

    if (!max_remaining_effort && !capacity) {
        return DEFAULT_REMAINING_EFFORT;
    }

    const maximum = max([max_remaining_effort ? max_remaining_effort : 0, capacity ? capacity : 0]);

    return maximum ? maximum : DEFAULT_REMAINING_EFFORT;
}

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

import { max } from "d3-array";
import { select } from "d3-selection";
import { curveMonotoneX } from "d3-shape";
import {
    buildGraphScales,
    getDaysToDisplay,
    drawCurve,
    drawIdealLine,
    buildChartLayout,
    TimeScaleLabelsFormatter,
} from "@tuleap/chart-builder";
import type { BurndownData } from "../../type";

import type {
    ChartPropsWithoutTooltip,
    PropertiesBuilderGraph,
    XYScale,
} from "@tuleap/chart-builder";
import { addScaleLines } from "../chart-scale-drawer";
import { removeAllLabelsOverlapsOthersLabels } from "../time-scale-label-formatter";
import { getDisplayableData, getLastData } from "../chart-data-service";
import { addBadgeCaption } from "../chart-badge-generator";
import { getCoordinatesScaleLines } from "../chart-scale-helper";

const DEFAULT_REMAINING_EFFORT = 5;

export function createBurndownChart(
    chart_container: HTMLElement,
    chart_props: ChartPropsWithoutTooltip,
    burndown_data: BurndownData,
    id_milestone: number,
): void {
    const x_axis_tick_values = getDaysToDisplay(burndown_data),
        displayable_data = getDisplayableData(burndown_data.points_with_date),
        y_axis_maximum = getMaxRemainingEffort(burndown_data);

    const properties: PropertiesBuilderGraph = {
        ...chart_props,
        x_axis_tick_values,
        y_axis_maximum,
    };

    const { x_scale, y_scale }: XYScale = buildGraphScales(properties);

    const coordinates_scale_lines = getCoordinatesScaleLines({ x_scale, y_scale }, y_axis_maximum);

    const first_ideal_line_point = burndown_data.capacity ? burndown_data.capacity : y_axis_maximum;

    const nb_ticks = 4,
        tick_padding = 5;

    drawBurndownChart();

    function drawBurndownChart(): void {
        if (!coordinates_scale_lines) {
            return;
        }

        const svg_burndown = buildChartLayout(
            chart_container,
            chart_props,
            { x_scale, y_scale },
            nb_ticks,
            tick_padding,
        );

        drawIdealLine(
            svg_burndown,
            { x_scale, y_scale },
            { line_start: first_ideal_line_point, line_end: 0 },
        );
        select(chart_container).selectAll("circle").remove();
        select(chart_container).selectAll(".chart-y-axis > .tick > line").remove();

        addScaleLines(svg_burndown, coordinates_scale_lines);

        new TimeScaleLabelsFormatter({
            layout: svg_burndown,
            first_date: x_axis_tick_values[0],
            last_date: x_axis_tick_values[x_axis_tick_values.length - 1],
        }).formatTicks();

        removeAllLabelsOverlapsOthersLabels(svg_burndown);
        drawCurve(
            svg_burndown,
            { x_scale, y_scale },
            displayable_data,
            "remaining_effort",
            curveMonotoneX,
        );

        const last_point = getLastData(displayable_data);
        if (!last_point) {
            return;
        }

        const x_scale_last_date = x_scale(last_point.date);

        if (!x_scale_last_date) {
            return;
        }

        if (last_point.remaining_effort === 0) {
            return;
        }

        let y_scale_effort = y_scale(last_point.remaining_effort);
        if (!y_scale_effort) {
            y_scale_effort = 0;
        }

        addBadgeCaption(
            y_scale_effort,
            x_scale_last_date,
            last_point.remaining_effort,
            svg_burndown,
            id_milestone,
        );
    }
}

export function getMaxRemainingEffort({ points_with_date, capacity }: BurndownData): number {
    const max_remaining_effort = max(
        points_with_date,
        ({ remaining_effort }: { remaining_effort: number | null }) => remaining_effort,
    );

    if (!max_remaining_effort && !capacity) {
        return DEFAULT_REMAINING_EFFORT;
    }

    const maximum = max([max_remaining_effort ? max_remaining_effort : 0, capacity ? capacity : 0]);

    return maximum ? maximum : DEFAULT_REMAINING_EFFORT;
}

/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import moment from "moment";
import { sprintf } from "sprintf-js";
import { max } from "d3-array";
import { curveLinear } from "d3-shape";
import { select } from "d3-selection";
import { gettext_provider } from "./gettext-provider.js";
import {
    buildGraphScales,
    buildChartLayout,
    TooltipFactory,
    ColumnFactory,
    TimeScaleLabelsFormatter,
    getDaysToDisplay,
    addTextCaption,
    addBadgeCaption,
    addContentCaption,
    drawIdealLine,
    drawCurve,
} from "@tuleap/chart-builder";

import { getLastDayData, getDisplayableData } from "./chart-data-service.js";

export function createBurndownChart({
    chart_container,
    chart_props,
    chart_legends,
    burndown_data,
}) {
    const tooltip_factory = new TooltipFactory({
        tooltip_margin_bottom: 30,
        tooltip_padding_width: 10,
        tooltip_padding_height: 5,
        tooltip_arrow_size: 150,
        tooltip_font_size: 12,
    });

    const DEFAULT_REMAINING_EFFORT = 5,
        x_axis_tick_values = getDaysToDisplay(burndown_data),
        displayable_data = getDisplayableData(burndown_data.points_with_date),
        last_day_data = getLastDayData(burndown_data.points_with_date),
        y_axis_maximum = getMaxRemainingEffort(burndown_data);

    const properties = {
        ...chart_props,
        x_axis_tick_values,
        y_axis_maximum,
    };

    const { x_scale, y_scale } = buildGraphScales(properties);

    const column_factory = new ColumnFactory({
        x_scale,
        y_scale,
        column_width: x_scale.step(),
        column_height: y_scale(0) - properties.margins.top,
    });

    const svg_burndown = buildChartLayout(chart_container, chart_props, {
        x_scale,
        y_scale,
    });

    insertLegend();

    const label_formatter = new TimeScaleLabelsFormatter({
        layout: svg_burndown,
        first_date: x_axis_tick_values[0],
        last_date: x_axis_tick_values[x_axis_tick_values.length - 1],
    });

    label_formatter.formatTicks();

    if (!burndown_data.points_with_date.length) {
        return;
    }

    drawBurndownChart();

    function drawBurndownChart() {
        addIdealLine();
        drawDataColumns();
        addCurve("remaining_effort");
        setInteraction();
    }

    function drawDataColumns() {
        const columns = svg_burndown
            .selectAll(".chart-datum-column")
            .data(displayable_data)
            .enter()
            .append("g")
            .attr("class", "chart-datum-column");

        columns.each(function ({ date, remaining_effort }) {
            const column = select(this);

            column_factory.addColumn(column, date);

            column
                .append("circle")
                .attr("class", "chart-plot-remaining-effort chart-tooltip-target")
                .attr("cx", x_scale(date))
                .attr("cy", y_scale(remaining_effort))
                .attr("r", 4);
        });
    }

    function addCurve(line_name) {
        drawCurve(
            svg_burndown,
            {
                x_scale,
                y_scale,
            },
            displayable_data,
            line_name,
            curveLinear,
        );
    }

    function setInteraction() {
        svg_burndown.selectAll(".chart-datum-column").each(function () {
            const datum_column = select(this);
            datum_column.on("mouseenter", () => {
                highlightColumn(datum_column);
            });

            datum_column.on("mouseleave", () => {
                ceaseHighlight();
            });
        });
    }

    function highlightColumn(target_column) {
        ceaseHighlight();

        target_column.selectAll("circle").classed("highlighted", true);

        target_column.select(".chart-column").classed("highlighted", true);

        tooltip_factory
            .addTooltip(target_column)
            .addTextLine(({ date }) =>
                moment(date, moment.ISO_8601).format(properties.tooltip_date_format),
            )
            .addTextLine(({ remaining_effort }) =>
                sprintf(gettext_provider.gettext("Remaining effort: %s"), remaining_effort),
            );
    }

    function ceaseHighlight() {
        svg_burndown.selectAll("circle").classed("highlighted", false);
        svg_burndown.selectAll(".chart-column").classed("highlighted", false);

        TooltipFactory.removeTooltips(svg_burndown);
    }

    function addIdealLine() {
        const { capacity } = burndown_data;
        const first_ideal_line_point = capacity ? capacity : y_axis_maximum;

        drawIdealLine(
            svg_burndown,
            {
                x_scale,
                y_scale,
            },
            {
                line_start: first_ideal_line_point,
                line_end: 0,
            },
        );
    }

    function getDateLegendContent() {
        if (isThereARemainingEffort()) {
            return sprintf(
                chart_props.left_legend_title,
                moment(last_day_data.date).format(chart_props.left_legend_date_format),
            );
        }

        return sprintf(
            chart_props.left_legend_title,
            moment().format(chart_props.left_legend_date_format),
        );
    }

    function isThereARemainingEffort() {
        return (
            Object.prototype.hasOwnProperty.call(last_day_data, "remaining_effort") &&
            last_day_data.remaining_effort !== null
        );
    }

    function getMaxRemainingEffort({ points_with_date, capacity }) {
        const max_remaining_effort = max(
            points_with_date,
            ({ remaining_effort }) => remaining_effort,
        );

        const maximum = max([max_remaining_effort, capacity]);

        if (maximum) {
            return maximum;
        }

        return DEFAULT_REMAINING_EFFORT;
    }

    function insertLegend() {
        const legend_y_position = chart_props.margins.top * 0.5;
        const date_legend_content = getDateLegendContent();
        const badge_value = isThereARemainingEffort()
            ? last_day_data.remaining_effort
            : chart_props.legend_badge_default;

        addTextCaption({
            layout: svg_burndown,
            content: date_legend_content,
            legend_y_position,
        });

        addBadgeCaption({
            layout: svg_burndown,
            badge_value,
            legend_y_position,
        });

        addContentCaption({
            legend_y_position,
            layout: svg_burndown,
            chart_content_legend: chart_legends,
            chart_width: chart_props.graph_width,
            chart_margin_right: chart_props.margins.right,
        });
    }
}

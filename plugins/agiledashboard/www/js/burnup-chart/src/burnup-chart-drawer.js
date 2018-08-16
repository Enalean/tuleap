/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
import { select } from "d3-selection";
import { gettext_provider } from "./gettext-provider.js";
import { buildGraphScales } from "charts-builders/line-chart-scales-factory.js";
import { buildChartLayout } from "charts-builders/chart-layout-builder.js";
import { TooltipFactory } from "charts-builders/chart-tooltip-factory.js";
import { ColumnFactory } from "charts-builders/chart-column-factory.js";
import { TimeScaleLabelsFormatter } from "charts-builders/time-scale-labels-formatter.js";
import { getDaysToDisplay } from "charts-builders/chart-dates-service.js";
import { addTextCaption } from "charts-builders/chart-text-legend-generator.js";
import { addBadgeCaption } from "charts-builders/chart-badge-legend-generator.js";
import { addContentCaption } from "charts-builders/chart-content-legend-generator.js";

import { drawIdealLine, drawCurve } from "charts-builders/chart-lines-service.js";

import { getLastDayData, getDisplayableData } from "./chart-data-service.js";

export { createBurnupChart };

function createBurnupChart({ chart_container, chart_props, chart_legends, burnup_data }) {
    const tooltip_factory = new TooltipFactory({
        tooltip_margin_bottom: 25,
        tooltip_padding_width: 15,
        tooltip_padding_height: 5,
        tooltip_arrow_size: 150,
        tooltip_font_size: 12
    });

    const default_total_effort = 5,
        x_axis_tick_values = getDaysToDisplay(burnup_data),
        displayable_data = getDisplayableData(burnup_data.points_with_date),
        last_day_data = getLastDayData(burnup_data.points_with_date),
        total_effort = getTotalEffort(burnup_data);

    const properties = {
        ...chart_props,
        x_axis_tick_values,
        y_axis_maximum: total_effort
    };

    const { x_scale, y_scale } = buildGraphScales(properties);

    const column_factory = new ColumnFactory({
        x_scale,
        y_scale,
        column_width: x_scale.step(),
        column_height: y_scale(0) - properties.margins.top
    });

    const svg_burnup = buildChartLayout(chart_container, chart_props, {
        x_scale,
        y_scale
    });

    insertLegend();

    const label_formatter = new TimeScaleLabelsFormatter({
        layout: svg_burnup,
        first_date: x_axis_tick_values[0],
        last_date: x_axis_tick_values[x_axis_tick_values.length - 1]
    });

    label_formatter.formatTicks();

    if (!burnup_data.points_with_date.length) {
        last_day_data.date = moment();

        return;
    }

    drawBurnupChart(chart_container);

    function drawBurnupChart() {
        addIdealLine();
        drawDataColumns();
        addCurve("total");
        addCurve("team");
        setInteraction();
    }

    function drawDataColumns() {
        const columns = svg_burnup
            .selectAll(".chart-datum-column")
            .data(displayable_data)
            .enter()
            .append("g")
            .attr("class", "chart-datum-column");

        columns.each(function({ date, total_effort, team_effort }) {
            const column = select(this);

            column_factory.addColumn(column, date);

            column
                .append("circle")
                .attr("class", "chart-plot-total-effort")
                .attr("cx", x_scale(date))
                .attr("cy", y_scale(total_effort))
                .attr("r", 4);

            column
                .append("circle")
                .attr("class", "chart-plot-team-effort chart-tooltip-target")
                .attr("cx", x_scale(date))
                .attr("cy", y_scale(team_effort))
                .attr("r", 4);
        });
    }

    function addCurve(line_name) {
        drawCurve(
            svg_burnup,
            {
                x_scale,
                y_scale
            },
            displayable_data,
            line_name
        );
    }

    function setInteraction() {
        svg_burnup.selectAll(".chart-datum-column").each(function() {
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
                moment(date, moment.ISO_8601).format(properties.tooltip_date_format)
            )
            .addTextLine(({ team_effort }) =>
                sprintf(gettext_provider.gettext("Team effort: %s"), team_effort)
            )
            .addTextLine(({ total_effort }) =>
                sprintf(gettext_provider.gettext("Total effort: %s"), total_effort)
            );
    }

    function ceaseHighlight() {
        svg_burnup.selectAll("circle").classed("highlighted", false);
        svg_burnup.selectAll(".chart-column").classed("highlighted", false);

        TooltipFactory.removeTooltips(svg_burnup);
    }

    function addIdealLine() {
        const final_total_effort = last_day_data.total_effort
            ? last_day_data.total_effort
            : burnup_data.capacity;

        drawIdealLine(
            svg_burnup,
            {
                x_scale,
                y_scale
            },
            {
                line_start: 0,
                line_end: final_total_effort
            }
        );
    }

    function getTotalEffort({ points_with_date, capacity }) {
        const max_total_effort = max(points_with_date, ({ total_effort }) => total_effort);

        if (max_total_effort) {
            return max_total_effort;
        }

        if (capacity) {
            return capacity;
        }

        return default_total_effort;
    }

    function isThereATeamEffort() {
        return last_day_data.hasOwnProperty("team_effort") && last_day_data.team_effort !== null;
    }

    function getDateLegendContent() {
        if (isThereATeamEffort()) {
            return sprintf(
                chart_props.left_legend_title,
                moment(last_day_data.date).format(chart_props.left_legend_date_format)
            );
        }

        return sprintf(
            chart_props.left_legend_title,
            moment().format(chart_props.left_legend_date_format)
        );
    }

    function insertLegend() {
        const legend_y_position = chart_props.margins.top * 0.5;
        const date_legend_content = getDateLegendContent();
        const badge_value = isThereATeamEffort()
            ? last_day_data.team_effort
            : chart_props.legend_badge_default;

        addTextCaption({
            layout: svg_burnup,
            content: date_legend_content,
            legend_y_position
        });

        addBadgeCaption({
            layout: svg_burnup,
            badge_value,
            legend_y_position
        });

        addContentCaption({
            legend_y_position,
            layout: svg_burnup,
            chart_content_legend: chart_legends,
            chart_width: chart_props.graph_width,
            chart_margin_right: chart_props.margins.right
        });
    }
}

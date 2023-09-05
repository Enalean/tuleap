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
import moment from "moment";
import { sprintf } from "sprintf-js";
import { max } from "d3-array";
import { select } from "d3-selection";
import { curveLinear } from "d3-shape";
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

export { createBurnupChart };

function createBurnupChart({
    chart_container,
    chart_props,
    chart_legends,
    generic_burnup_data,
    mode,
}) {
    const tooltip_factory = new TooltipFactory({
        tooltip_margin_bottom: 25,
        tooltip_padding_width: 15,
        tooltip_padding_height: 5,
        tooltip_arrow_size: 150,
        tooltip_font_size: 12,
    });

    const default_total_effort = 5,
        x_axis_tick_values = getDaysToDisplay(generic_burnup_data),
        displayable_data = getDisplayableData(generic_burnup_data),
        last_day_data = getLastDayData(generic_burnup_data),
        total_effort = getTotal(generic_burnup_data);

    const properties = {
        ...chart_props,
        x_axis_tick_values,
        y_axis_maximum: total_effort,
    };

    const { x_scale, y_scale } = buildGraphScales(properties);

    const column_factory = new ColumnFactory({
        x_scale,
        y_scale,
        column_width: x_scale.step(),
        column_height: y_scale(0) - properties.margins.top,
    });

    const svg_burnup = buildChartLayout(chart_container, chart_props, {
        x_scale,
        y_scale,
    });

    insertLegend();

    const label_formatter = new TimeScaleLabelsFormatter({
        layout: svg_burnup,
        first_date: x_axis_tick_values[0],
        last_date: x_axis_tick_values[x_axis_tick_values.length - 1],
    });

    label_formatter.formatTicks();

    if (!generic_burnup_data.points_with_date.length) {
        last_day_data.date = moment();

        return;
    }

    drawBurnupChart(mode);

    function drawBurnupChart(mode) {
        addIdealLine();
        drawDataColumns();
        addCurve("total");
        addCurve("progression");
        setInteraction(mode);
    }

    function drawDataColumns() {
        const columns = svg_burnup
            .selectAll(".chart-datum-column")
            .data(displayable_data)
            .enter()
            .append("g")
            .attr("class", "chart-datum-column");

        columns.each(function (column_data) {
            const column = select(this);
            const date = column_data.date;

            column_factory.addColumn(column, date);

            column
                .append("circle")
                .attr("class", "chart-plot-total-effort")
                .attr("cx", x_scale(date))
                .attr("cy", y_scale(column_data.total))
                .attr("r", 4);

            column
                .append("circle")
                .attr("class", "chart-plot-team-effort chart-tooltip-target")
                .attr("cx", x_scale(date))
                .attr("cy", y_scale(column_data.progression))
                .attr("r", 4);
        });
    }

    function addCurve(line_name) {
        drawCurve(
            svg_burnup,
            {
                x_scale,
                y_scale,
            },
            displayable_data,
            line_name,
            curveLinear,
        );
    }

    function setInteraction(mode) {
        svg_burnup.selectAll(".chart-datum-column").each(function () {
            const datum_column = select(this);
            datum_column.on("mouseenter", () => {
                highlightColumn(datum_column, mode);
            });

            datum_column.on("mouseleave", () => {
                ceaseHighlight();
            });
        });
    }

    function highlightColumn(target_column, mode) {
        ceaseHighlight();

        target_column.selectAll("circle").classed("highlighted", true);

        target_column.select(".chart-column").classed("highlighted", true);

        let progression_label = gettext_provider.gettext("Team effort: %s");
        if (mode === "count") {
            progression_label = gettext_provider.gettext("Closed elements: %s");
        }

        let total_label = gettext_provider.gettext("Total effort: %s");
        if (mode === "count") {
            total_label = gettext_provider.gettext("Total elements: %s");
        }

        tooltip_factory
            .addTooltip(target_column)
            .addTextLine(({ date }) =>
                moment(date, moment.ISO_8601).format(properties.tooltip_date_format),
            )
            .addTextLine(({ progression }) => sprintf(progression_label, progression))
            .addTextLine(({ total }) => sprintf(total_label, total));
    }

    function ceaseHighlight() {
        svg_burnup.selectAll("circle").classed("highlighted", false);
        svg_burnup.selectAll(".chart-column").classed("highlighted", false);

        TooltipFactory.removeTooltips(svg_burnup);
    }

    function getLastDayDataTotal() {
        return last_day_data.total;
    }

    function addIdealLine() {
        const total = getLastDayDataTotal() ? getLastDayDataTotal() : generic_burnup_data.capacity;

        drawIdealLine(
            svg_burnup,
            {
                x_scale,
                y_scale,
            },
            {
                line_start: 0,
                line_end: total,
            },
        );
    }

    function getTotal({ points_with_date, capacity }) {
        const max_total = max(points_with_date, ({ total }) => total);

        if (max_total) {
            return max_total;
        }

        if (capacity) {
            return capacity;
        }

        return default_total_effort;
    }

    function isThereALastDayProgression() {
        return (
            Object.prototype.hasOwnProperty.call(last_day_data, "progression") &&
            last_day_data.progression !== null
        );
    }

    function getDateLegendContent() {
        if (isThereALastDayProgression()) {
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

    function getLastDayDataProgression() {
        return last_day_data.progression;
    }

    function insertLegend() {
        const legend_y_position = chart_props.margins.top * 0.5;
        const date_legend_content = getDateLegendContent();
        const badge_value = isThereALastDayProgression()
            ? getLastDayDataProgression()
            : chart_props.legend_badge_default;

        addTextCaption({
            layout: svg_burnup,
            content: date_legend_content,
            legend_y_position,
        });

        addBadgeCaption({
            layout: svg_burnup,
            badge_value,
            legend_y_position,
        });

        addContentCaption({
            legend_y_position,
            layout: svg_burnup,
            chart_content_legend: chart_legends,
            chart_width: chart_props.graph_width,
            chart_margin_right: chart_props.margins.right,
        });
    }
}

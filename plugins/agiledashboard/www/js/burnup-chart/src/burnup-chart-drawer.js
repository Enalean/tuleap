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
import moment                from 'moment';
import { sprintf }           from 'sprintf-js';
import { max, extent }       from 'd3-array';
import { line }              from 'd3-shape';
import { select, selectAll } from 'd3-selection';
import { gettext_provider }  from './gettext-provider.js';
import { buildGraphScales }  from './burnup-chart-scales-factory.js';
import { buildChartLayout }  from 'charts-builders/chart-layout-builder.js';
import { TooltipFactory }    from 'charts-builders/chart-tooltip-factory.js';
import {
    getLastDayData,
    getDisplayableData,
} from './chart-data-service.js';

import {
    getDaysToDisplay,
    getGranularity
} from 'charts-builders/chart-dates-service.js';

export { createBurnupChart };

function createBurnupChart({
    chart_container,
    chart_props,
    chart_legends,
    burnup_data
}) {
    const tooltip_factory = new TooltipFactory({
        tooltip_margin_bottom : 30,
        tooltip_padding_width : 15,
        tooltip_padding_height: 5,
        tooltip_arrow_size    : 150,
        tooltip_font_size     : 12
    });

    const default_total_effort = 5,
          x_axis_tick_values   = getDaysToDisplay(burnup_data),
          displayable_data     = getDisplayableData(burnup_data.points),
          last_day_data        = getLastDayData(burnup_data.points),
          total_effort         = getTotalEffort(burnup_data);

    const properties = {
        ...chart_props,
        x_axis_tick_values,
        total_effort
    };

    const {
        x_scale,
        y_scale
    } = buildGraphScales(properties);

    const end_date              = x_axis_tick_values[x_axis_tick_values.length - 1];
    const timeframe_granularity = getGranularity(x_axis_tick_values[0], end_date);

    const svg_burnup = buildChartLayout(
        chart_container,
        chart_props,
        chart_legends,
        last_day_data,
        {
            x_scale,
            y_scale
        },
        timeframe_granularity
    );

    if (! burnup_data.points.length) {
        last_day_data.date = moment();

        return;
    }

    last_day_data.date = moment(
        last_day_data.date,
        moment.ISO_8601
    )
    .endOf('day')
    .toISOString();

    drawBurnupChart(chart_container);

    function drawBurnupChart() {
        drawIdealLine();
        drawDataColumns();
        drawCurve('total');
        drawCurve('team');
        setInteraction();
        highlightLastColumn();
    }

    function drawDataColumns() {
        const column_width = x_scale.step();
        const y_domain     = y_scale.domain();

        const columns = svg_burnup.selectAll('.chart-datum-column')
            .data(displayable_data)
            .enter()
                .append('g')
                .attr('class', 'chart-datum-column');

        columns.each(function(point) {
            const column = select(this);

            column.append('rect')
                .attr('class', 'chart-column')
                .attr('x', () => {
                    const x_position = x_scale(moment(point.date, moment.ISO_8601).format('YYYY-MM-DD'));

                    if (isFirstColumn(point)) {
                        return x_position;
                    }

                    return x_position - column_width / 2;
                })
                .attr('y', y_scale(y_domain[1]))
                .attr('width', () => {
                    if (isFirstColumn(point) || isLastColumn(point)) {
                        return column_width / 2;
                    }

                    return column_width;
                })
                .attr('height', y_scale(0) - properties.margins.top);

            column.append('circle')
                .attr('class', `chart-plot-total-effort`)
                .attr('cx', ({ date }) => x_scale(moment(date, moment.ISO_8601).format('YYYY-MM-DD')))
                .attr('cy', ({ total_effort }) => y_scale(total_effort))
                .attr('r', 4);

            column.append('circle')
                .attr('class', `chart-plot-team-effort chart-tooltip-target`)
                .attr('cx', ({ date }) => x_scale(moment(date, moment.ISO_8601).format('YYYY-MM-DD')))
                .attr('cy', ({ team_effort }) => y_scale(team_effort))
                .attr('r', 4);
        });
    }

    function drawCurve(line_name) {
        const lines = line()
            .x(({ date }) => x_scale(moment(date, moment.ISO_8601).format('YYYY-MM-DD')))
            .y((point) => y_scale(point[`${ line_name }_effort`]));

        svg_burnup.append('path')
            .data([displayable_data])
            .attr('class', `chart-curve-${ line_name }-effort`)
            .attr('d', lines);
    }

    function setInteraction() {
        svg_burnup.selectAll('.chart-datum-column')
            .each(function() {
                const datum_column = select(this);
                datum_column.on('mouseenter', () => {
                    highlightColumn(datum_column);
                });

                datum_column.on('mouseleave', () => {
                    ceaseHighlight();
                });
            });
    }

    function highlightLastColumn() {
        const columns = selectAll('.chart-datum-column').nodes();

        if (! columns.length) {
            return;
        }

        const last_column = select(columns.pop());

        highlightColumn(last_column);
    }

    function highlightColumn(target_column) {
        ceaseHighlight();

        target_column.selectAll('circle')
            .classed('highlighted', true);

        target_column.select('.chart-column')
            .classed('highlighted', true);

        tooltip_factory.addTooltip(target_column)
            .addTextLine(({ date }) => moment(date, moment.ISO_8601).format(properties.tooltip_date_format))
            .addTextLine(({ team_effort }) => sprintf(gettext_provider.gettext('Team effort: %s'), team_effort))
            .addTextLine(({ total_effort }) => sprintf(gettext_provider.gettext('Total effort: %s'), total_effort));
    }

    function ceaseHighlight() {
        selectAll('circle').classed('highlighted', false);
        selectAll('.chart-column').classed('highlighted', false);

        TooltipFactory.removeTooltips();
    }

    function isFirstColumn({ date }) {
        const [ x_minimum ] = x_scale.domain();
        const point         = moment(date, moment.ISO_8601);
        const first_column  = moment(x_minimum, moment.ISO_8601);

        return first_column.isSame(point, 'day');
    }

    function isLastColumn({ date }) {
        const x_domain    = x_scale.domain();
        const x_maximum   = x_domain[ x_domain.length - 1];
        const point       = moment(date, moment.ISO_8601);
        const last_column = moment(x_maximum, moment.ISO_8601);

        return last_column.isSame(point, 'day');
    }

    function drawIdealLine() {
        const [
            x_minimum,
            x_maximum
        ] = extent(x_scale.domain());

        const final_total_effort = (last_day_data.total_effort) ? last_day_data.total_effort : burnup_data.capacity;

        const coordinates = [
            {
                x_coordinate: x_scale(moment(x_minimum, moment.ISO_8601).format('YYYY-MM-DD')),
                y_coordinate: y_scale(0)
            }, {
                x_coordinate: x_scale(moment(x_maximum, moment.ISO_8601).format('YYYY-MM-DD')),
                y_coordinate: y_scale(final_total_effort)
            }
        ];

        const ideal_line = svg_burnup.append('g')
            .attr('class', 'ideal-line');

        const ideal_line_generator = line()
            .x(({x_coordinate}) => x_coordinate)
            .y(({y_coordinate}) => y_coordinate);

        ideal_line.selectAll('.chart-plot-ideal-burnup')
            .data(coordinates)
            .enter()
            .append('circle')
            .attr('class', 'chart-plot-ideal-burnup')
            .attr('cx', ({x_coordinate}) => x_coordinate)
            .attr('cy', ({y_coordinate}) => y_coordinate)
            .attr('r', 4);

        ideal_line.append('path')
            .datum(coordinates)
            .attr('class', 'chart-curve-ideal-line')
            .attr('d', ideal_line_generator);
    }

    function getTotalEffort({points, capacity}) {
        const max_total_effort = max(points, ({ total_effort }) => total_effort);

        if (max_total_effort) {
            return max_total_effort;
        }

        if (capacity) {
            return capacity;
        }

        return default_total_effort;
    }
}

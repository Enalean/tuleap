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

import moment                   from 'moment';
import { sprintf }              from 'sprintf-js';
import { select, selectAll }    from 'd3-selection';
import { axisLeft, axisBottom } from 'd3-axis';
import { gettext_provider }     from './gettext-provider.js';
import { getDifference }        from './chart-dates-service.js';
import {
    getBadgeProperties,
    getElementsWidth,
    getElementSpacing,
    getYAxisTicksSize
} from './chart-layout-service.js';

export { buildChartLayout };

moment.locale(gettext_provider.locale);

const WEEK  = 'week';
const MONTH = 'month';

const localized_date_formats = {
    day        : gettext_provider.gettext('ddd DD'),
    month      : gettext_provider.gettext('MMM YYYY'),
    week       : gettext_provider.gettext('WW'),
    /// Week format prefix. Chart ticks will be rendered like W01 for week 01, W02 for week 02 and so on.
    week_prefix: gettext_provider.gettext('W %s')
};

function buildChartLayout(
    chart_container,
    {
        graph_width,
        graph_height,
        margins,
    },
    legend_config,
    badge_data,
    scales,
    timeframe_granularity
) {
    const layout = drawSVG(chart_container, graph_width, graph_height);
    const axes   = initAxis(
        graph_width,
        margins,
        scales,
        timeframe_granularity
    );
    drawAxis(layout, axes, graph_height, margins);
    addLegend(
        layout,
        graph_width,
        margins,
        badge_data,
        legend_config
    );

    ticksEvery(timeframe_granularity);

    return layout;
}

function drawSVG(element, width, height) {
    return select(element)
        .append('svg')
        .attr('width', width)
        .attr('height', height);
}

function initAxis(
    graph_width,
    margins,
    { x_scale, y_scale },
    timeframe_granularity
) {
    const tick_formatter = getTickFormatter(timeframe_granularity, localized_date_formats);

    const y_ticks_size = getYAxisTicksSize(
        graph_width,
        margins.right,
        margins.left
    );

    return {
        x_axis: initXAxis(x_scale, tick_formatter),
        y_axis: initYAxis(y_scale, y_ticks_size)
    };
}

function initXAxis(x_scale, tick_formatter) {
    return axisBottom(x_scale)
        .tickFormat(tick_formatter)
        .tickPadding(20);
}

function initYAxis(y_scale, y_ticks_size) {
    return axisLeft(y_scale)
        .ticks(10)
        .tickSize(-y_ticks_size)
        .tickPadding(20);
}

function drawAxis(
    layout,
    { x_axis, y_axis },
    graph_height,
    { left, bottom }
) {
    const y_position = graph_height - bottom;

    layout.append('g')
        .attr('class', 'chart-x-axis')
        .attr('transform', `translate(0, ${y_position})`)
        .call(x_axis);

    layout.append('g')
        .attr('class', 'chart-y-axis')
        .attr('transform', `translate(${left}, 0)`)
        .call(y_axis);

    selectAll('.domain').remove();
    selectAll('.chart-x-axis > .tick > line').remove();
}

function addLegend(
    layout,
    graph_width,
    margins,
    badge_data = {},
    graph_legends
) {
    const legend_y_position   = margins.top * 0.66;
    const current_date        = moment(badge_data.date).format(localized_date_formats.day);
    const current_team_effort = badge_data.team_effort || gettext_provider.gettext('n/k');

    const legend = layout.append('g')
        .attr('class', 'chart-legend chart-text-grey');

    const left_legend = legend.append('g')
        .attr('class', 'legend-left');

    left_legend.append('text')
        .attr('y', legend_y_position)
        .text(sprintf(graph_legends.title, current_date));

    appendBadge(
        left_legend,
        current_team_effort,
        legend_y_position
    );

    const right_legend = legend.append('g')
        .attr('class', 'legend-right');

    right_legend.selectAll('text')
        .data(graph_legends.bullets)
        .enter()
            .append('g')
            .attr('class', 'legend-item')
                .append('text')
                .attr('class', 'chart-curve-label')
                .text(({ label }) => label);

    const widths = getElementsWidth(selectAll('.chart-curve-label'));

    selectAll('.legend-item')
        .each(function(label, index) {
            const previous_label_width = getElementSpacing(widths, index, 30, 20);

            select(this)
                .attr('transform', `translate(${previous_label_width}, 0)`);
        });

    right_legend.selectAll('circle')
        .data(graph_legends.bullets)
        .enter()
            .append('circle')
            .attr('class', ({ classname }) => classname)
            .attr('cy', -4)
            .attr('cx', ((data, index) => getElementSpacing(widths, index, 30, 10)))
            .attr('r', 5);

    const right_legend_length = graph_width - right_legend.node().getBBox().width - margins.left;

    right_legend.attr('transform', `translate(${ right_legend_length }, ${ legend_y_position })`);
}

function appendBadge(container, current_team_effort, legend_y_position) {
    const { width } = container.node().getBBox();
    const badge     = container.append('g')
        .attr('transform', `translate(${ width + 10 }, ${ legend_y_position })`)
        .attr('class', 'chart-badge');

    badge.append('text')
        .attr('id', 'chart-badge-value')
        .attr('x', 10)
        .text(current_team_effort);

    const badge_props = getBadgeProperties(select('#chart-badge-value'), 10, 2);

    badge.append('rect')
        .attr('width', badge_props.width)
        .attr('height', badge_props.height)
        .attr('y', badge_props.y)
        .attr('x', badge_props.x)
        .attr('rx', 10)
        .attr('ry', 10);
}

function ticksEvery(timeframe_granularity) {
    const all_ticks = selectAll(`.chart-x-axis > .tick`).nodes();
    let previous_label;

    all_ticks.forEach((node) => {
        const label = select(node).text();

        if (! previous_label) {
            previous_label = label;
            return;
        }

        if (label === previous_label) {
            select(node).remove();
            return;
        }

        previous_label = label;
    });

    const displayed_ticks = selectAll(`.chart-x-axis > .tick`).nodes();

    if (
        canFirstLabelOverlapSecondLabel(
            displayed_ticks[0],
            displayed_ticks[1],
            timeframe_granularity
        )
    ) {
        select(displayed_ticks[0]).remove();
    }
}

function canFirstLabelOverlapSecondLabel(first_tick, second_tick, timeframe_granularity) {
    if (timeframe_granularity !== MONTH) {
        return false;
    }

    const first_label  = select(first_tick);
    const second_label = select(second_tick);

    const { weeks } = getDifference(first_label.datum(), second_label.datum());

    return weeks < 2;
}

function getTickFormatter(timeframe_granularity) {
    const tick_format = localized_date_formats[ timeframe_granularity ];

    if (timeframe_granularity === WEEK) {
        const prefix = localized_date_formats.week_prefix;

        return function(date) {
            return sprintf(prefix, moment(date, moment.ISO_8601).format(tick_format));
        };
    }

    return function(date) {
        return moment(date, moment.ISO_8601).format(tick_format);
    };
}

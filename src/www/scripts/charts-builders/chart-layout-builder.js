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
import { select }               from 'd3-selection';
import { axisLeft, axisBottom } from 'd3-axis';
import { gettext_provider }     from './gettext-provider.js';
import {
    getBadgeProperties,
    getElementsWidth,
    getElementSpacing,
    getYAxisTicksSize
} from './chart-layout-service.js';

export { buildChartLayout };

const day_date_format = gettext_provider.gettext('ddd DD');

function buildChartLayout(
    chart_container,
    {
        graph_width,
        graph_height,
        margins,
    },
    scales,
    legend_config,
    badge_data
) {
    gettext_provider.setLocale(document.body.dataset.userLocale);

    const layout = drawSVG(chart_container, graph_width, graph_height);
    const axes   = initAxis(
        graph_width,
        margins,
        scales
    );
    drawAxis(layout, axes, graph_height, margins);

    if (legend_config) {
        addLegend(
            layout,
            graph_width,
            margins,
            badge_data,
            legend_config
        );
    }

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
    { x_scale, y_scale }
) {
    const y_ticks_size = getYAxisTicksSize(
        graph_width,
        margins.right,
        margins.left
    );

    return {
        x_axis: initXAxis(x_scale),
        y_axis: initYAxis(y_scale, y_ticks_size)
    };
}

function initXAxis(x_scale) {
    return axisBottom(x_scale)
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

    layout.selectAll('.domain').remove();
    layout.selectAll('.chart-x-axis > .tick > line').remove();
}

function addLegend(
    layout,
    graph_width,
    margins,
    badge_data,
    graph_legends
) {
    const legend_y_position = margins.top * 0.5;
    const current_date      = moment(badge_data.date).format(day_date_format);
    const badge_value       = badge_data.value;

    const legend = layout.append('g')
        .attr('class', 'chart-legend chart-text-grey');

    const left_legend = legend.append('g')
        .attr('class', 'legend-left');

    left_legend.append('text')
        .attr('y', legend_y_position)
        .text(sprintf(graph_legends.title, current_date));

    appendBadge(
        left_legend,
        badge_value,
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

    const widths = getElementsWidth(right_legend.selectAll('.chart-curve-label'));

    right_legend.selectAll('.legend-item')
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

    const right_legend_length = graph_width - right_legend.node().getBBox().width - margins.right;

    right_legend.attr('transform', `translate(${ right_legend_length }, ${ legend_y_position })`);
}

function appendBadge(container, badge_value, legend_y_position) {
    const { width } = container.node().getBBox();
    const badge     = container.append('g')
        .attr('transform', `translate(${ width + 10 }, ${ legend_y_position })`)
        .attr('class', 'chart-badge');

    badge.append('text')
        .attr('id', 'chart-badge-value')
        .attr('x', 10)
        .text(badge_value);

    const badge_props = getBadgeProperties(badge.select('#chart-badge-value'), 10, 2);

    badge.append('rect')
        .attr('width', badge_props.width)
        .attr('height', badge_props.height)
        .attr('y', badge_props.y)
        .attr('x', badge_props.x)
        .attr('rx', badge_props.height / 2)
        .attr('ry', badge_props.height / 2);
}

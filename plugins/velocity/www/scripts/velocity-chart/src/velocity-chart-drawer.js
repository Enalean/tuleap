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

import { select }              from 'd3-selection';
import { buildBarChartScales } from 'charts-builders/bar-chart-scales-factory.js';
import { buildChartLayout }    from 'charts-builders/chart-layout-builder.js';

export class VelocityChartDrawer {
    constructor({
        mount_point,
        chart_props,
        sprints_data
    }) {
        Object.assign(this, {
            mount_point,
            chart_props,
            sprints_data
        });

        this.init();
    }

    init() {
        this.initScales();
        this.initLayout();
    }

    draw() {
        this.drawBars();
    }

    initScales() {
        const scales = buildBarChartScales({
            ...this.chart_props,
            x_axis_tick_values: this.getXLabels(),
            y_axis_maximum    : this.getMaximumVelocity()
        });

        Object.assign(this, scales);
    }

    initLayout() {
        const {
            mount_point,
            chart_props,
            x_scale,
            y_scale
        } = this;

        this.svg_velocity = buildChartLayout(
            mount_point,
            chart_props,
            {
                x_scale,
                y_scale
            }
        );
    }

    drawBars() {
        const {
            x_scale,
            y_scale,
            chart_props
        } = this;

        const columns = this.svg_velocity.selectAll('.velocity-bar')
            .data(this.sprints_data)
            .enter()
            .append('g')
            .attr('class', 'velocity-bar');

        columns.each(function({ name, velocity }) {
            const column = select(this);

            column.append('rect')
                .attr('class', 'velocity-chart-bar')
                .attr('x', x_scale(name))
                .attr('y', y_scale(velocity))
                .attr('width', x_scale.bandwidth())
                .attr('height', chart_props.minimum_bar_height + y_scale(0) - y_scale(velocity));
        });
    }

    getXLabels() {
        return this.sprints_data.map(sprint => sprint.name);
    }

    getMaximumVelocity() {
        if (this.sprints_data.length === 0) {
            return this.chart_props.default_max_velocity;
        }

        const velocities = this.sprints_data.map(({ velocity }) => velocity);
        const maximum    = Math.max(...velocities);

        return (maximum) ? maximum : this.chart_props.default_max_velocity;
    }
}

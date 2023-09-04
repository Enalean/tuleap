/*
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
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

import moment from "moment";
import { select } from "d3-selection";
import { sprintf } from "sprintf-js";
import {
    buildBarChartScales,
    buildChartLayout,
    TooltipFactory,
    truncate,
} from "@tuleap/chart-builder";
import { gettext_provider } from "./gettext-provider.js";

export class VelocityChartDrawer {
    constructor({ mount_point, chart_props, sprints_data }) {
        Object.assign(this, {
            mount_point,
            chart_props,
            sprints_data,
        });

        this.init();
    }

    init() {
        this.initScales();
        this.initLayout();
        this.initTooltipFactory();
    }

    draw() {
        this.drawBars();
        this.harmonizeLabels();
        this.addTooltips();
    }

    initScales() {
        const scales = buildBarChartScales({
            ...this.chart_props,
            x_axis_tick_values: this.getXLabels(),
            y_axis_maximum: this.getMaximumVelocity(),
        });

        Object.assign(this, scales);
    }

    initLayout() {
        const { mount_point, chart_props, x_scale, y_scale } = this;

        this.svg_velocity = buildChartLayout(mount_point, chart_props, {
            x_scale,
            y_scale,
        });
    }

    initTooltipFactory() {
        const tooltip_factory = new TooltipFactory({
            tooltip_margin_bottom: 25,
            tooltip_padding_width: 15,
            tooltip_padding_height: 5,
            tooltip_arrow_size: 150,
            tooltip_font_size: 12,
        });

        Object.assign(this, { tooltip_factory });
    }

    drawBars() {
        const { x_scale, y_scale, chart_props } = this;

        const columns = this.svg_velocity
            .selectAll(".velocity-bar")
            .data(this.sprints_data)
            .enter()
            .append("g")
            .attr("class", "velocity-bar");

        columns.each(function ({ name, velocity }) {
            const column = select(this);

            column
                .append("rect")
                .attr("class", "velocity-chart-bar chart-tooltip-target")
                .attr("x", x_scale(name))
                .attr("y", y_scale(velocity))
                .attr("width", x_scale.bandwidth())
                .attr("height", chart_props.minimum_bar_height + y_scale(0) - y_scale(velocity));
        });
    }

    getXLabels() {
        return this.sprints_data.map(({ name }) => name);
    }

    getMaximumVelocity() {
        if (this.sprints_data.length === 0) {
            return this.chart_props.default_max_velocity;
        }

        const velocities = this.sprints_data.map(({ velocity }) => velocity);
        const maximum = Math.max(...velocities);

        return maximum ? maximum : this.chart_props.default_max_velocity;
    }

    addTooltips() {
        this.svg_velocity
            .selectAll(".velocity-bar")
            .on("mouseenter", (data) => {
                const target_bar = select(data.target);

                this.tooltip_factory
                    .addTooltip(target_bar)
                    .addTextLine(({ name }) => {
                        if (name.length >= 50) {
                            return name.substring(0, 30) + "...";
                        }

                        return name;
                    })
                    .addTextLine((sprint) => this.getSprintDates(sprint))
                    .addTextLine(({ velocity }) => {
                        return sprintf(gettext_provider.gettext("Velocity: %s"), velocity);
                    });
            })
            .on("mouseleave", () => {
                TooltipFactory.removeTooltips(this.svg_velocity);
            });
    }

    getSprintDates({ start_date, duration }) {
        const sprint_start = moment(start_date, moment.ISO_8601).format(
            this.chart_props.tooltip_date_format,
        );

        const sprint_end = moment(start_date, moment.ISO_8601)
            .add(duration, "days")
            .format(this.chart_props.tooltip_date_format);

        return sprint_start + " 🠦 " + sprint_end;
    }

    harmonizeLabels() {
        const sprint_names = this.svg_velocity.selectAll(`.chart-x-axis > .tick`).nodes();
        const step_size = this.x_scale.step();

        sprint_names.forEach((node) => {
            const label = select(node);

            let label_width = node.getBBox().width;

            if (label_width < step_size) {
                return;
            }

            const truncation_size = step_size - this.chart_props.abcissa_labels_margin;

            truncate(truncation_size, label);
        });
    }
}

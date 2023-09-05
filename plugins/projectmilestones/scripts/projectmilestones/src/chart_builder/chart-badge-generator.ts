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

import type { Selection } from "d3-selection";
import { getContainerProperties } from "./chart-badge-services";

const Y_AXIS_TO_CENTER = 5,
    Y_AXIS_TO_CENTER_FLAG = 1,
    X_AXIS_TO_CENTER_FLAG = 2;

export function addBadgeCaption(
    badge_y_position: number,
    badge_x_position: number,
    badge_value: number,
    layout: Selection<SVGSVGElement, unknown, null, undefined>,
    id_milestone: number,
): void {
    const badge = layout
        .append("g")
        .attr("class", "release-chart-badge-remaining")
        .attr(
            "transform",
            `translate(${badge_x_position}, ${badge_y_position + Y_AXIS_TO_CENTER})`,
        );

    const badge_content = buildBadgeContent();
    const badge_props = getContainerProperties(badge_content, badge_value);

    buildBadgeBackground();
    addIconFlag();

    badge.append("use").attr("xlink:href", `#release-chart-burndown-badge-value-${id_milestone}`);

    function addIconFlag(): void {
        badge
            .append("text")
            .attr("style", "font-family:'Font Awesome 6 Free';")
            .attr("class", "release-chart-end-icon fa")
            .attr("x", badge_props.x + badge_props.width / 2 - X_AXIS_TO_CENTER_FLAG)
            .attr("y", badge_props.y - Y_AXIS_TO_CENTER_FLAG)
            .text(function () {
                return "\uf11e";
            }); // fa-flag-checkered
    }

    function buildBadgeContent(): Selection<SVGTextElement, unknown, null, undefined> {
        return badge
            .append("text")
            .attr("id", `release-chart-burndown-badge-value-${id_milestone}`)
            .attr("class", "release-chart-badge-value")
            .text(badge_value);
    }

    function buildBadgeBackground(): void {
        badge
            .append("rect")
            .attr("width", badge_props.width)
            .attr("height", badge_props.height)
            .attr("y", badge_props.y)
            .attr("x", badge_props.x)
            .attr("rx", badge_props.height / 2)
            .attr("ry", badge_props.height / 2)
            .attr("class", "release-chart-badge-container");
    }
}

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

import { getCaptionGroupFromLayout } from "./chart-legend-service.js";

export function addBadgeCaption({ legend_y_position, badge_value, layout }) {
    const caption = getCaptionGroupFromLayout(layout);

    const { width } = caption.node().getBBox();
    const badge = caption
        .append("g")
        .attr("transform", `translate(${width + 10}, ${legend_y_position})`)
        .attr("class", "chart-badge");

    const badge_content = badge
        .append("text")
        .attr("id", "chart-badge-value")
        .attr("x", 10)
        .text(badge_value);

    const badge_props = getBadgeProperties(badge_content, 10, 2);

    badge
        .append("rect")
        .attr("width", badge_props.width)
        .attr("height", badge_props.height)
        .attr("y", badge_props.y)
        .attr("x", badge_props.x)
        .attr("rx", badge_props.height / 2)
        .attr("ry", badge_props.height / 2);
}

function getBadgeProperties(badge_value_selection, padding_width, padding_height) {
    const { width, height, x } = badge_value_selection.node().getBBox();

    return {
        width: width + 2 * padding_width,
        height: height + 2 * padding_height,
        x: x - padding_width,
        y: -height + padding_height,
    };
}

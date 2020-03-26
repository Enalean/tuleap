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
import { getElementSpacing, getElementsWidth } from "./chart-layout-service";
import { select } from "d3-selection";

export function addContentCaption({
    layout,
    legend_y_position,
    chart_content_legend,
    chart_width,
    chart_margin_right,
}) {
    const caption = getCaptionGroupFromLayout(layout);
    const right_caption = caption.append("g").attr("class", "legend-right");

    right_caption
        .selectAll("text")
        .data(chart_content_legend)
        .enter()
        .append("g")
        .attr("class", "legend-item")
        .append("text")
        .attr("class", "chart-curve-label")
        .text(({ label }) => label);

    const widths = getElementsWidth(right_caption.selectAll(".chart-curve-label"));

    right_caption.selectAll(".legend-item").each(function (label, index) {
        const previous_label_width = getElementSpacing(widths, index, 30, 20);

        select(this).attr("transform", `translate(${previous_label_width}, 0)`);
    });

    right_caption
        .selectAll("circle")
        .data(chart_content_legend)
        .enter()
        .append("circle")
        .attr("class", ({ classname }) => classname)
        .attr("cy", -4)
        .attr("cx", (data, index) => getElementSpacing(widths, index, 30, 10))
        .attr("r", 5);

    moveCaptionToRight(right_caption, chart_width, chart_margin_right, legend_y_position);
}

function moveCaptionToRight(legend, chart_width, chart_margin_right, legend_y_position) {
    const right_caption_length = chart_width - legend.node().getBBox().width - chart_margin_right;

    legend.attr("transform", `translate(${right_caption_length}, ${legend_y_position})`);
}

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

import type { Selection, BaseType } from "d3-selection";
import { select } from "d3-selection";

const PADDING_OVERLAPS = 2;

export function removeAllLabelsOverlapsOthersLabels(
    svg_burndown: Selection<SVGSVGElement, unknown, null, undefined>,
): void {
    let index = 0;
    let second_index = index + 1;

    const displayed_ticks = svg_burndown.selectAll(`.chart-x-axis > .tick`).nodes();

    while (second_index < displayed_ticks.length) {
        const first_tick = getElementFromBaseType(displayed_ticks[index]);
        const second_tick = getElementFromBaseType(displayed_ticks[second_index]);

        const x_end_first_tick_box = first_tick.getBoundingClientRect().right;
        const x_beginning_second_tick_box = second_tick.getBoundingClientRect().left;

        if (x_end_first_tick_box + PADDING_OVERLAPS >= x_beginning_second_tick_box) {
            select(second_tick).remove();
        } else {
            index = second_index;
        }
        second_index++;
    }
}

function getElementFromBaseType(tick: BaseType): Element {
    if (!(tick instanceof Element)) {
        throw new Error("Ticks is not an Element");
    }
    return tick;
}

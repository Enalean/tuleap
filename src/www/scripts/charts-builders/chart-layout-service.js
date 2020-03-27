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

export { getElementSpacing, getElementsWidth, getYAxisTicksSize };

function getElementSpacing(elements_widths, element_index, element_padding, base_padding) {
    if (element_index === 0) {
        return base_padding;
    }

    let sum = base_padding;

    for (let index = 0; index < element_index; index++) {
        sum += elements_widths[index] + element_padding;
    }

    return sum;
}

function getElementsWidth(selection) {
    const nodes = selection.nodes();
    return nodes.map((node) => node.getBBox().width);
}

function getYAxisTicksSize(graph_width, margins_right, margins_left) {
    return graph_width - margins_right - margins_left;
}

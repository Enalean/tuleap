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

import type { XYSizeElement } from "../type";
import type { Selection } from "d3-selection";
const PADDING_ONE_DIGIT = 7.5;
const PADDING_WIDTH = 5;
const PADDING_HEIGHT = 2;

export function getContainerProperties(
    badge_content: Selection<SVGTextElement, unknown, null, undefined>,
    badge_value: number,
): XYSizeElement {
    const node = badge_content.node();
    if (!node) {
        throw new Error("Badge content does not exist.");
    }
    const { width, height, x, y } = node.getBBox();
    const width_box_with_padding = width + 2 * PADDING_WIDTH;
    const height_box_with_padding = height + 2 * PADDING_HEIGHT;

    return {
        width: getWidth(),
        height: height_box_with_padding,
        x: getX(),
        y: y - PADDING_HEIGHT,
    };

    function getWidth(): number {
        return isOnlyOneDigit() ? height_box_with_padding : width_box_with_padding;
    }

    function getX(): number {
        return isOnlyOneDigit() ? x - PADDING_ONE_DIGIT : x - PADDING_WIDTH;
    }

    function isOnlyOneDigit(): boolean {
        if (!Number.isInteger(badge_value)) {
            return false;
        }

        return badge_value < 10;
    }
}

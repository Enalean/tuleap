/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import type { Symbol as D3Symbol } from "d3-shape";

type TooltipBottomMargin = number;
type TooltipPaddingWidth = number;
type TooltipPaddingHeight = number;
type TooltipArrowSize = number;
type TooltipFontSize = number;

type TooltipFactoryParameters = {
    tooltip_margin_bottom: TooltipBottomMargin;
    tooltip_padding_width: TooltipPaddingWidth;
    tooltip_padding_height: TooltipPaddingHeight;
    tooltip_arrow_size: TooltipArrowSize;
    tooltip_font_size: TooltipFontSize;
};

type TooltipCoordinates = {
    tooltip_x: number;
    tooltip_y: number;
    arrow_x: number;
    arrow_y: number;
    arrow_angle: 0 | 180;
};

type OrientationX = number;
type OrientationY = number;
type OrientationWidth = number;
type OrientationHeight = number;

type TooltipOrientationParameter = {
    x: OrientationX;
    y: OrientationY;
    width: OrientationWidth;
    height: OrientationHeight;
};

export class TooltipFactory {
    tooltip_width: number;
    tooltip_height: number;
    tooltip_middle: number;
    tooltip_margin_bottom: TooltipBottomMargin;
    tooltip_padding_width: TooltipPaddingWidth;
    tooltip_padding_height: TooltipPaddingHeight;
    tooltip_arrow_size: TooltipArrowSize;
    tooltip_font_size: TooltipFontSize;

    constructor(params: TooltipFactoryParameters);

    addTooltip(column: Selection<SVGElement, unknown, null, undefined>): this;

    static removeTooltips(container: Selection<SVGSVGElement, unknown, null, undefined>): void;

    getTooltipWidth(): number;

    getTooltipHeight(): number;

    getTooltipTextHeight(): number;

    getTooltipCoordinates(): TooltipCoordinates;

    getTooltipArrow(): D3Symbol<unknown, undefined>;

    resizeTheBubble(): void;

    centerText(): void;

    positionBubble(): void;

    addTextLine(text: string): this;

    getTooltipOrientation(orientation: TooltipOrientationParameter): TooltipCoordinates;

    shouldTooltipBeAtTheBottom(target_y: number): boolean;

    getBottomOrientedTooltipCoordinates(
        x: OrientationX,
        y: OrientationY,
        width: OrientationWidth,
        height: OrientationHeight,
    ): TooltipCoordinates;

    getTopOrientedTooltipCoordinates(
        x: OrientationX,
        y: OrientationY,
        width: OrientationWidth,
    ): TooltipCoordinates;
}

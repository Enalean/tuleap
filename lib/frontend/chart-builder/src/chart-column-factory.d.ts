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

import type { ScalePoint, ScaleLinear } from "d3-scale";
import type { Selection } from "d3-selection";

type XScale = ScalePoint<string>;
type YScale = ScaleLinear<number, number>;
type ColumnWidth = number;
type ColumnHeight = number;

type ColumnFactoryParameters = {
    x_scale: XScale;
    y_scale: YScale;
    column_width: ColumnWidth;
    column_height: ColumnHeight;
};

type DateValue = string;

export class ColumnFactory {
    x_scale: XScale;
    y_scale: YScale;
    column_width: ColumnWidth;
    column_height: ColumnHeight;

    constructor(param: ColumnFactoryParameters);

    addColumn(container: Selection<SVGSVGElement>, date: DateValue): void;

    isFirstColumn(date: DateValue): boolean;

    isLastColumn(date: DateValue): boolean;
}

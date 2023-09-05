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

import type { PointsWithDate, XYScale } from "./type";
import type { Selection } from "d3-selection";
import type { CurveFactory } from "d3-shape";
import type { PointsWithDateForGenericBurnup } from "../../../plugins/agiledashboard/scripts/burnup-chart/src/type";

export function drawIdealLine(
    container: Selection<SVGSVGElement, unknown, null, undefined>,
    { x_scale, y_scale }: XYScale,
    { line_start, line_end }: { line_start: number; line_end: number },
): void;

export function drawCurve(
    container: Selection<SVGSVGElement, unknown, null, undefined>,
    { x_scale, y_scale }: XYScale,
    dataset: PointsWithDate[] | PointsWithDateForGenericBurnup[],
    line_name: string,
    interpolation: CurveFactory,
): void;

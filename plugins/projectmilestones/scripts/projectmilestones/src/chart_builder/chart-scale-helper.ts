/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { XYScale } from "@tuleap/chart-builder";
import type { XYMinMaxCoordinates } from "../type";
import { extent } from "d3-array";

export function getCoordinatesScaleLines(
    { x_scale, y_scale }: XYScale,
    y_axis_maximum: number,
): null | XYMinMaxCoordinates {
    const [x_minimum, x_maximum] = extent(x_scale.domain());

    if (!x_minimum || !x_maximum) {
        return null;
    }

    const x_scale_minimum = x_scale(x_minimum);
    const x_scale_maximum = x_scale(x_maximum);

    if (!x_scale_minimum || !x_scale_maximum) {
        return null;
    }

    let y_scale_zero = y_scale(0);
    if (!y_scale_zero) {
        y_scale_zero = 0;
    }

    let y_scale_max = y_scale(y_axis_maximum);
    if (!y_scale_max) {
        y_scale_max = 0;
    }

    return {
        x_coordinate_minimum: x_scale_minimum,
        y_coordinate_minimum: y_scale_zero,
        x_coordinate_maximum: x_scale_maximum,
        y_coordinate_maximum: y_scale_max,
    };
}

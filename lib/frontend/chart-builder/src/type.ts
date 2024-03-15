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

import type * as d3Scale from "d3-scale";

export interface PropertiesBuilderGraph {
    margins: MarginsGraph;
    graph_width: number;
    graph_height: number;
    y_axis_maximum: number;
    x_axis_tick_values: string[];
}

export interface XYScale {
    x_scale: d3Scale.ScalePoint<string>;
    y_scale: d3Scale.ScaleLinear<number, number>;
}

export interface ChartPropsWithoutTooltip {
    graph_width: number;
    graph_height: number;
    margins: MarginsGraph;
}

export interface MarginsGraph {
    top: number;
    right: number;
    bottom: number;
    left: number;
}

export interface PointsWithDate {
    date: string;
    remaining_effort: number | null;
}

export interface DaysDisplayingBurndownData {
    opening_days: Array<number>;
    duration: number | null;
    points_with_date: Array<{ date: string }>;
    start_date: string;
}

export interface PointsWithDateForGenericBurnup {
    date: string;
    total: number | null;
    progression: number | null;
}

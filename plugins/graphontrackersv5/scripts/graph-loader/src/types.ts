/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
export const TYPE_BAR = "bar";
export const TYPE_GROUPED_BAR = "groupedbar";
export const TYPE_PIE = "pie";
export const TYPE_CUMULATIVE_FLOW = "cumulativeflow";

export interface BarData {
    readonly type: typeof TYPE_BAR;
    readonly title: string;
    readonly height: string;
    readonly width: string;
    readonly legend: ReadonlyArray<string>;
    readonly data: ReadonlyArray<number>;
    readonly colors: ReadonlyArray<string>;
}

export interface GroupedBarDataValue {
    readonly label: string;
    readonly values: ReadonlyArray<{ label: string; value: number }>;
}

export interface GroupedBarData {
    readonly type: typeof TYPE_GROUPED_BAR;
    readonly title: string;
    readonly height: string;
    readonly width: string;
    readonly legend: ReadonlyArray<string>;
    readonly grouped_labels: ReadonlyArray<string>;
    readonly values: ReadonlyArray<GroupedBarDataValue>;
    readonly colors: ReadonlyArray<{ label: string; color: string }>;
}

export interface PieData {
    readonly type: typeof TYPE_PIE;
    readonly title: string;
    readonly height: string;
    readonly width: string;
    readonly legend: ReadonlyArray<string>;
    readonly data: ReadonlyArray<string>;
    readonly colors: ReadonlyArray<string>;
}

export interface CumulativeFlowDataValue {
    readonly label: string;
    readonly color: string;
    readonly values: ReadonlyArray<{ date: number; count: number }>;
}

export interface CumulativeFlowData {
    readonly type: typeof TYPE_CUMULATIVE_FLOW;
    readonly height: string;
    readonly width: string;
    readonly data: ReadonlyArray<CumulativeFlowDataValue>;
}

export type GraphData = BarData | GroupedBarData | PieData | CumulativeFlowData;

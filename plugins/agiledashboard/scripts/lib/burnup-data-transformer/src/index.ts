/**
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

import type { PointsWithDateForGenericBurnup } from "@tuleap/chart-builder";

export { transformToGenericBurnupData } from "./burnup-data-transformer";
export type { PointsWithDateForGenericBurnup };

export interface BurnupData {
    start_date: string;
    duration: number | null;
    capacity: number | null;
    is_under_calculation: boolean;
    opening_days: Array<number>;
    points_with_date: Array<PointsWithDateForBurnup>;
    points_with_date_count_elements: Array<PointsCountElements>;
    label: string | null;
}

export interface PointsWithDateForBurnup {
    date: string;
    team_effort: number;
    total_effort: number;
}

export interface PointsCountElements {
    date: string;
    closed_elements: number;
    total_elements: number;
}

export interface GenericBurnupData {
    start_date: string;
    duration: number | null;
    capacity: number | null;
    is_under_calculation: boolean;
    opening_days: Array<number>;
    points_with_date: PointsWithDateForGenericBurnup[];
}

export interface PointsNotNullWithDateForGenericBurnup {
    date: string;
    total: number;
    progression: number;
}

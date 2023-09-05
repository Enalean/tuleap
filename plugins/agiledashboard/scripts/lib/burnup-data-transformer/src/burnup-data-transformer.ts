/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import type { BurnupData, GenericBurnupData } from "./index";

export function transformToGenericBurnupData(
    burnup_data: BurnupData,
    mode: "count" | "effort",
): GenericBurnupData {
    let points;
    if (mode === "count") {
        points = burnup_data.points_with_date_count_elements.map(function (base_point) {
            return {
                date: base_point.date,
                total: base_point.total_elements,
                progression: base_point.closed_elements,
            };
        });
    } else {
        points = burnup_data.points_with_date.map(function (base_point) {
            return {
                date: base_point.date,
                total: base_point.total_effort,
                progression: base_point.team_effort,
            };
        });
    }

    const { start_date, duration, capacity, is_under_calculation, opening_days } = burnup_data;
    return {
        start_date,
        duration,
        capacity,
        is_under_calculation,
        opening_days,
        points_with_date: points,
    };
}

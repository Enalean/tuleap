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

import { getFormattedDates } from "@tuleap/chart-builder";
import type { PointsWithDate } from "@tuleap/chart-builder";
import type { PointsNotNullWithDate } from "../type";
import type {
    PointsNotNullWithDateForGenericBurnup,
    PointsWithDateForGenericBurnup,
} from "@tuleap/plugin-agiledashboard-burnup-data-transformer";

export function getDisplayableData(dataset: PointsWithDate[]): PointsNotNullWithDate[] {
    const formatted_data = getFormattedDates(dataset);
    const points_not_null: PointsNotNullWithDate[] = [];

    formatted_data.forEach((point) => {
        const remaining_effort = point.remaining_effort;
        if (remaining_effort !== null) {
            points_not_null.push({ ...point, remaining_effort });
        }
    });

    return points_not_null;
}

export function getLastData(dataset: PointsNotNullWithDate[]): PointsNotNullWithDate | null {
    if (!dataset.length) {
        return null;
    }

    return dataset[dataset.length - 1];
}

export function getLastGenericBurnupData(
    dataset: PointsWithDateForGenericBurnup[],
): PointsWithDateForGenericBurnup | null {
    if (!dataset.length) {
        return null;
    }

    return dataset[dataset.length - 1];
}

export function getDisplayableDataForBurnup(
    generic_burnup_data: PointsWithDateForGenericBurnup[],
): PointsNotNullWithDateForGenericBurnup[] {
    const formatted_data = getFormattedDates(generic_burnup_data);
    const points_not_null: PointsNotNullWithDateForGenericBurnup[] = [];

    formatted_data.forEach((point) => {
        const total = point.total;
        const progression = point.progression;
        if (total !== null && progression !== null) {
            points_not_null.push({ ...point, total, progression });
        }
    });

    return points_not_null;
}

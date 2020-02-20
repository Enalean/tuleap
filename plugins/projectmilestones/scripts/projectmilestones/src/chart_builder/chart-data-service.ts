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

import { getFormattedDates } from "../../../../../../src/www/scripts/charts-builders/chart-dates-service";
import { PointsWithDate } from "../../../../../../src/www/scripts/charts-builders/type";
import { PointsNotNullWithDate } from "../type";
import { GenericBurnupDatas } from "../../../../../agiledashboard/scripts/burnup-chart/src/type";

export { getDisplayableData, getLastData, getLastGenericBurnupData };

function getDisplayableData(dataset: PointsWithDate[]): PointsNotNullWithDate[] {
    const formatted_data = getFormattedDates(dataset);
    const points_not_null: PointsNotNullWithDate[] = [];

    formatted_data.forEach(point => {
        const remaining_effort = point.remaining_effort;
        if (remaining_effort !== null) {
            points_not_null.push({ ...point, remaining_effort });
        }
    });

    return points_not_null;
}

function getLastData(dataset: PointsNotNullWithDate[]): PointsNotNullWithDate | null {
    if (!dataset.length) {
        return null;
    }

    return dataset[dataset.length - 1];
}

function getLastGenericBurnupData(dataset: GenericBurnupDatas[]): GenericBurnupDatas | null {
    if (!dataset.length) {
        return null;
    }

    return dataset[dataset.length - 1];
}

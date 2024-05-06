/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

export function getLastDayData(dataset) {
    if (!dataset.length) {
        return {};
    }

    return dataset[dataset.length - 1];
}

export function getDisplayableData(dataset, x_axis) {
    const filtered_data = dataset.filter(
        ({ remaining_effort, date }) =>
            remaining_effort !== null && x_axis.includes(date.substring(0, 10)),
    );

    return getFormattedDates(filtered_data);
}

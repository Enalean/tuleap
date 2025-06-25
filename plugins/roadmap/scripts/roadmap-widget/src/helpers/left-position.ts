/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
import { Styles } from "./styles";
import type { TimePeriod } from "../type";
import type { DateTime } from "luxon";

export function getLeftForDate(date: DateTime, time_period: TimePeriod): number {
    let left = 0;
    let i = 1;
    while (i < time_period.units.length && time_period.units[i] < date) {
        left += Styles.TIME_UNIT_WIDTH_IN_PX;
        i++;
    }

    if (i < time_period.units.length) {
        const current_unit = time_period.units[i - 1];
        const next_unit = time_period.units[i];
        const ms_since_beginning_of_unit = Number(date.diff(current_unit).toObject().milliseconds);
        const ms_in_the_unit = Number(next_unit.diff(current_unit).toObject().milliseconds);
        left += (Styles.TIME_UNIT_WIDTH_IN_PX * ms_since_beginning_of_unit) / ms_in_the_unit;
    }

    return Math.round(left);
}

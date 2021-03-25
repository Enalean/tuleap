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
import { getBeginningOfNextNthMonth } from "./beginning-of-next-nth-month";

export function getLeftForDate(date: Date, months: Date[]): number {
    let left = 0;
    let i = 1;
    while (i < months.length && months[i] < date) {
        left += Styles.TIME_UNIT_WIDTH_IN_PX;
        i++;
    }

    const current_month = getBeginningOfNextNthMonth(date, 0);
    const next_month = getBeginningOfNextNthMonth(date, 1);
    const ms_since_beginning_of_month = date.getTime() - current_month.getTime();
    const ms_in_the_month = next_month.getTime() - current_month.getTime();
    left += (Styles.TIME_UNIT_WIDTH_IN_PX * ms_since_beginning_of_month) / ms_in_the_month;

    return Math.round(left);
}

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

export function getMonths(start: Date | null, end: Date | null): Date[] {
    if (!start || !end) {
        return [];
    }
    if (start > end) {
        return [];
    }

    const months = [new Date(start.setDate(1))];
    let i = 1;
    while (months[months.length - 1] < end) {
        const same_date_months_later = new Date(new Date(start).setMonth(start.getMonth() + i++));
        const beginning_of_month = new Date(same_date_months_later.setDate(1));
        months.push(beginning_of_month);
    }

    return months;
}

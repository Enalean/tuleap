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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

export function getObsolescenceDateValueInput(select_date_value: string): string {
    const current_date = new Date();
    let date;
    switch (select_date_value) {
        case "permanent":
            date = "";
            break;
        case "today":
            date = current_date.toISOString().split("T")[0];
            break;
        default: {
            const months_to_add = parseInt(select_date_value, 10);
            const future_date = new Date(current_date);
            future_date.setMonth(future_date.getMonth() + months_to_add);
            date = future_date.toISOString().split("T")[0];
        }
    }
    return date;
}

export function formatObsolescenceDateValue(date_value: string): string {
    if (date_value === "") {
        return "";
    }

    const date = new Date(date_value);
    return date.toISOString().split("T")[0];
}

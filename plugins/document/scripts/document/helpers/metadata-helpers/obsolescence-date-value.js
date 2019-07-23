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
import moment from "moment/moment";

export function getObsolescenceDateValueInput(select_date_value) {
    const current_date = moment();
    let date;
    switch (select_date_value) {
        case "permanent":
            date = null;
            break;
        case "today":
            date = current_date.format("YYYY-MM-DD");
            break;
        default:
            date = moment(current_date, "YYYY-MM-DD")
                .add(select_date_value, "M")
                .format("YYYY-MM-DD");
    }
    return date;
}

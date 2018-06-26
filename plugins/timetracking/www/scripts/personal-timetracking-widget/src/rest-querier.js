/*
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import { get } from "tlp";
import { formatDatetimeToISO } from "./time-formatters.js";

export { getTrackedTimes };

async function getTrackedTimes(start_date, end_date, limit, offset) {
    const query = JSON.stringify({
        start_date: formatDatetimeToISO(start_date),
        end_date: formatDatetimeToISO(end_date)
    });

    const response = await get("/api/v1/timetracking", {
        params: {
            limit,
            offset,
            query
        }
    });

    const total = response.headers.get("X-PAGINATION-SIZE");
    const times = await response.json();

    return {
        times,
        total
    };
}

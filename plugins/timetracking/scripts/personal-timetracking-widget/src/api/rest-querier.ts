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

import { get, post, put, del } from "@tuleap/tlp-fetch";
import { formatDatetimeToISO } from "@tuleap/plugin-timetracking-time-formatters";
import type { PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";

const headers = {
    "content-type": "application/json",
};

export type TotalTimes = {
    readonly times: PersonalTime[];
    readonly total: number;
};

export async function getTrackedTimes(
    user_id: number,
    start_date: string,
    end_date: string,
    limit: number,
    offset: number,
): Promise<TotalTimes> {
    const query = JSON.stringify({
        start_date: formatDatetimeToISO(start_date),
        end_date: formatDatetimeToISO(end_date),
    });

    const response = await get(`/api/v1/users/${user_id}/timetracking`, {
        params: {
            limit,
            offset,
            query,
        },
    });
    const total: number =
        response.headers.get("X-PAGINATION-SIZE") === null
            ? 0
            : Number(response.headers.get("X-PAGINATION-SIZE"));
    const times: PersonalTime[] = await response.json();

    return {
        times,
        total,
    };
}
export async function postTime(
    date: string,
    artifact_id: number,
    time_value: string,
    step: string,
): Promise<PersonalTime> {
    const body = JSON.stringify({
        date_time: date,
        artifact_id: artifact_id,
        time_value: time_value,
        step: step,
    });

    const response = await post("/api/v1/timetracking", {
        headers,
        body,
    });

    const time = await response.json();
    return time;
}

export async function putTime(
    date_time: string,
    time_id: number,
    time_value: string,
    step: string,
): Promise<PersonalTime> {
    const body = JSON.stringify({
        date_time: date_time,
        time_value: time_value,
        step: step,
    });
    const response = await put("/api/v1/timetracking/" + time_id, {
        headers,
        body,
    });
    const time = await response.json();
    return time;
}

export async function delTime(time_id: number): Promise<void> {
    await del("/api/v1/timetracking/" + time_id, {
        headers,
    });
}

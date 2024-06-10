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

import type { ResultAsync } from "neverthrow";
import { decodeJSON, del, getResponse, postJSON, putJSON, uri } from "@tuleap/fetch-result";
import { formatDatetimeToISO } from "@tuleap/plugin-timetracking-time-formatters";
import type { Fault } from "@tuleap/fault";
import type { PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";

export type TotalTimes = {
    readonly times: PersonalTime[];
    readonly total: number;
};

export function getTrackedTimes(
    user_id: number,
    start_date: string,
    end_date: string,
    limit: number,
    offset: number,
): ResultAsync<TotalTimes, Fault> {
    const query = JSON.stringify({
        start_date: formatDatetimeToISO(start_date),
        end_date: formatDatetimeToISO(end_date),
    });

    return getResponse(uri`/api/v1/users/${user_id}/timetracking`, {
        params: { query, limit, offset },
    }).andThen((response) => {
        const total = Number.parseInt(response.headers.get("X-PAGINATION-SIZE") ?? "0", 10);
        return decodeJSON<PersonalTime[]>(response).map((times) => ({
            times,
            total,
        }));
    });
}

export function postTime(
    date: string,
    artifact_id: number,
    time_value: string,
    step: string,
): ResultAsync<PersonalTime, Fault> {
    return postJSON(uri`/api/v1/timetracking`, {
        date_time: date,
        artifact_id: artifact_id,
        time_value: time_value,
        step: step,
    });
}

export function putTime(
    date_time: string,
    time_id: number,
    time_value: string,
    step: string,
): ResultAsync<PersonalTime, Fault> {
    return putJSON<PersonalTime>(uri`/api/v1/timetracking/${time_id}`, {
        date_time: date_time,
        time_id: time_id,
        time_value: time_value,
        step: step,
    });
}

export function delTime(time_id: number): ResultAsync<unknown, Fault> {
    return del(uri`/api/v1/timetracking/${time_id}`);
}

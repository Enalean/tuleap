/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
import type { Fault } from "@tuleap/fault";
import { getAllJSON, putJSON, uri } from "@tuleap/fetch-result";
import type { User } from "@tuleap/core-rest-api-types";
import { formatDatetimeToISO } from "@tuleap/plugin-timetracking-time-formatters";
import type { Query, QueryResults } from "../type";

export interface PutQueryResult {
    readonly viewable_users: User[];
    readonly no_more_viewable_users: User[];
}

export function putQuery(widget_id: number, query: Query): ResultAsync<PutQueryResult, Fault> {
    const formatted_user_list: { id: number }[] = [];
    query.users_list.forEach((user: User) => formatted_user_list.push({ id: user.id }));

    if (query.predefined_time_period !== "") {
        return putJSON(uri`/api/v1/timetracking_management_widget/${widget_id}`, {
            start_date: null,
            end_date: null,
            predefined_time_period: query.predefined_time_period,
            users: formatted_user_list,
        });
    }
    return putJSON(uri`/api/v1/timetracking_management_widget/${widget_id}`, {
        start_date: formatDatetimeToISO(query.start_date),
        end_date: formatDatetimeToISO(query.end_date),
        predefined_time_period: null,
        users: formatted_user_list,
    });
}

export function getTimes(widget_id: number): ResultAsync<QueryResults, Fault> {
    return getAllJSON(uri`/api/v1/timetracking_management_widget/${widget_id}/times`, {
        params: { limit: 50 },
    });
}

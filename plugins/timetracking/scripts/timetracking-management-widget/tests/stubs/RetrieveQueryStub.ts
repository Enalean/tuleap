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
import type { PredefinedTimePeriod } from "@tuleap/plugin-timetracking-predefined-time-periods";
import type { User } from "@tuleap/core-rest-api-types";
import type { Query, TimetrackingManagementQuery } from "../../src/query/QueryRetriever";
import { ref } from "vue";

let start_date: string = new Date().toISOString().split("T")[0];
let end_date: string = new Date().toISOString().split("T")[0];
let predefined_time_period: PredefinedTimePeriod | "" = "";
let users_list: User[] = [];
const has_the_query_been_modified = ref(false);

export const injected_query: Query = {
    getQuery: (): TimetrackingManagementQuery => {
        return {
            start_date: start_date,
            end_date: end_date,
            predefined_time_period: predefined_time_period,
            users_list: ref(users_list),
        };
    },
    setQuery: (
        start: string,
        end: string,
        period: "" | PredefinedTimePeriod,
        users: User[],
    ): void => {
        start_date = start;
        end_date = end;
        predefined_time_period = period;
        users_list = users;
    },
    has_the_query_been_modified,
    saveQuery: (): void => {},
    no_more_viewable_users: ref([]),
};

export const RetrieveQueryStub = {
    withDefaults: (users: User[]): Query => ({
        getQuery: (): TimetrackingManagementQuery => {
            return {
                start_date: start_date,
                end_date: end_date,
                predefined_time_period: predefined_time_period,
                users_list: ref(users),
            };
        },
        setQuery: (start, end, period, users): void => {
            start_date = start;
            end_date = end;
            predefined_time_period = period;
            users_list = users;
        },
        has_the_query_been_modified,
        saveQuery: (): void => {},
        no_more_viewable_users: ref([]),
    }),
};

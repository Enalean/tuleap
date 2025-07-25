/*
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
import { TODAY } from "@tuleap/plugin-timetracking-predefined-time-periods";
import type { User } from "@tuleap/core-rest-api-types";
import type { Ref } from "vue";
import { ref } from "vue";
import { putQuery } from "../api/rest-querier";

export type TimetrackingManagementQuery = {
    start_date: string;
    end_date: string;
    predefined_time_period: PredefinedTimePeriod | "";
    users_list: Ref<User[]>;
};

export type Query = {
    getQuery: () => TimetrackingManagementQuery;
    setQuery: (
        start: string,
        end: string,
        period: PredefinedTimePeriod | "",
        users: User[],
    ) => void;
    has_the_query_been_modified: Ref<boolean>;
    no_more_viewable_users: Ref<User[]>;
    saveQuery: (widget_id: number) => void;
};

export const QueryRetriever = (): Query => {
    let start_date = new Date().toISOString().split("T")[0];
    let end_date = new Date().toISOString().split("T")[0];
    let predefined_period: PredefinedTimePeriod | "" = TODAY;
    const users_list: Ref<User[]> = ref([]);
    const has_the_query_been_modified = ref(false);
    const no_more_viewable_users: Ref<User[]> = ref([]);

    const getQuery = (): TimetrackingManagementQuery => {
        return {
            start_date: start_date,
            end_date: end_date,
            predefined_time_period: predefined_period,
            users_list: users_list,
        };
    };

    const setQuery = (
        start: string,
        end: string,
        period: PredefinedTimePeriod | "",
        users: User[],
    ): void => {
        start_date = start;
        end_date = end;
        predefined_period = period;
        users_list.value = users.sort(compareUsers);
    };

    function compareUsers(a: User, b: User): number {
        return a.display_name.localeCompare(b.display_name, undefined, { numeric: true });
    }

    const saveQuery = (widget_id: number): void => {
        putQuery(widget_id, getQuery()).match(
            (result) => {
                users_list.value = result.viewable_users.sort(compareUsers);
                no_more_viewable_users.value = result.no_more_viewable_users;
            },
            () => {},
        );
    };

    return {
        getQuery,
        setQuery,
        has_the_query_been_modified,
        saveQuery,
        no_more_viewable_users,
    };
};

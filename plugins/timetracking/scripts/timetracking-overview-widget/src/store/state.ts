/*
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

import type { ProjectReference } from "@tuleap/core-rest-api-types";
import type {
    OverviewReportTracker,
    TrackerWithTimes,
} from "@tuleap/plugin-timetracking-rest-api-types";

const a_month_ago = new Date();
a_month_ago.setMonth(a_month_ago.getMonth() - 1);

export type ProjectTracker = OverviewReportTracker & {
    disabled: boolean;
};

type TimetrackingUser = {
    user_id: number;
    user_name: string;
};

export type OverviewWidgetState = {
    report_id: number;
    user_id: number;
    are_void_trackers_hidden: boolean;
    start_date: string;
    end_date: string;
    error_message: null | string;
    success_message: null | string;
    selected_trackers: OverviewReportTracker[];
    trackers_times: TrackerWithTimes[];
    is_loading: boolean;
    is_loading_trackers: boolean;
    is_report_saved: boolean;
    reading_mode: boolean;
    trackers: ProjectTracker[];
    trackers_ids: number[];
    projects: ProjectReference[];
    users: TimetrackingUser[];
    is_added_tracker: boolean;
    selected_user_id: number | null;
};

export const default_state: OverviewWidgetState = {
    report_id: 0,
    user_id: 0,
    are_void_trackers_hidden: false,
    start_date: a_month_ago.toISOString().split("T")[0],
    end_date: new Date().toISOString().split("T")[0],
    error_message: null,
    success_message: null,
    selected_trackers: [],
    trackers_times: [],
    is_loading: false,
    is_loading_trackers: false,
    is_report_saved: true,
    reading_mode: true,
    trackers: [],
    trackers_ids: [],
    projects: [],
    users: [],
    is_added_tracker: true,
    selected_user_id: null,
};

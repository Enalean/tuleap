/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { TrackerResponseNoInstance } from "@tuleap/plugin-tracker-rest-api-types";

export type OverviewReportTracker = Pick<TrackerResponseNoInstance, "id" | "label" | "project"> & {
    readonly uri: string;
};

export type OverviewReport = {
    readonly id: number;
    readonly uri: string;
    readonly trackers: OverviewReportTracker[];
    readonly invalid_trackers: OverviewReportTracker[];
};

export type UserTotalTrackerTimes = {
    readonly user_name: string;
    readonly user_id: number;
    readonly minutes: number;
};

export type TrackerWithTimes = OverviewReportTracker & {
    readonly time_per_user: UserTotalTrackerTimes[];
};

/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import type { User, ProjectResponse } from "@tuleap/core-rest-api-types";

export type Query = {
    start_date: string;
    end_date: string;
    predefined_time_period: PredefinedTimePeriod | "";
    users_list: User[];
};

interface TimesSpentInProject {
    readonly project: ProjectResponse;
    readonly minutes: number;
}

export interface UserTimes {
    readonly user: User;
    readonly times: TimesSpentInProject[];
}

export type QueryResults = readonly UserTimes[];

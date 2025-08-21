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
import type { Query } from "../../src/type";

const start_date: string = new Date().toISOString().split("T")[0];
const end_date: string = new Date().toISOString().split("T")[0];
const predefined_time_period: PredefinedTimePeriod | "" = "";

export const QueryStub = {
    withDefaults: (users_list: User[]): Query => ({
        start_date,
        end_date,
        predefined_time_period,
        users_list,
    }),
};

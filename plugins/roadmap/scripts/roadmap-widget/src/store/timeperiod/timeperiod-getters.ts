/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { getFirstDate } from "../../helpers/first-date";
import { getLastDate } from "../../helpers/last-date";
import type { Iteration, Task, TimePeriod } from "../../type";
import { TimePeriodWeek } from "../../helpers/time-period-week";
import { TimePeriodQuarter } from "../../helpers/time-period-quarter";
import { TimePeriodMonth } from "../../helpers/time-period-month";
import type { RootState } from "../type";
import type { TimeperiodState } from "./type";

export const first_date = (
    state: unknown,
    getters: unknown,
    root_state: RootState,
    root_getters: { "tasks/tasks": Task[] },
): Date => {
    const first_task_date = getFirstDate(root_getters["tasks/tasks"], root_state.now);

    const iterations_containing_first_task_date = getIterationsAroundDate(
        root_state,
        first_task_date,
    );

    if (iterations_containing_first_task_date.length === 0) {
        return first_task_date;
    }

    return iterations_containing_first_task_date.reduce((first_date, iteration) => {
        if (iteration.start < first_date) {
            return iteration.start;
        }

        return first_date;
    }, first_task_date);
};

export const last_date = (
    state: unknown,
    getters: unknown,
    root_state: RootState,
    root_getters: { "tasks/tasks": Task[] },
): Date => {
    return getLastDate(
        [
            ...root_getters["tasks/tasks"],
            ...root_state.iterations.lvl1_iterations,
            ...root_state.iterations.lvl2_iterations,
        ],
        root_state.now,
    );
};

export const time_period = (
    state: TimeperiodState,
    { first_date, last_date }: { first_date: Date; last_date: Date },
    root_state: RootState,
): TimePeriod => {
    if (state.timescale === "week") {
        return new TimePeriodWeek(
            getFirstDateWithOffset(7, first_date),
            last_date,
            root_state.gettext_provider,
        );
    }

    if (state.timescale === "quarter") {
        return new TimePeriodQuarter(
            getFirstDateWithOffset(90, first_date),
            last_date,
            root_state.gettext_provider,
        );
    }

    return new TimePeriodMonth(
        getFirstDateWithOffset(30, first_date),
        last_date,
        root_state.locale_bcp47,
    );
};

function getFirstDateWithOffset(nb_days_to_substract: number, first_date: Date): Date {
    const first_date_with_offset = new Date(first_date);
    first_date_with_offset.setUTCDate(first_date_with_offset.getUTCDate() - nb_days_to_substract);

    return first_date_with_offset;
}

function getIterationsAroundDate(root_state: RootState, date: Date): Iteration[] {
    return [
        ...root_state.iterations.lvl1_iterations,
        ...root_state.iterations.lvl2_iterations,
    ].filter((iteration: Iteration) => {
        return iteration.start <= date && date <= iteration.end;
    });
}

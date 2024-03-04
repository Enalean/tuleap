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
import type { IterationsState } from "./type";
import type { Iteration } from "../../type";
import type { DateTime } from "luxon";

export const lvl1_iterations_to_display = (
    state: IterationsState,
    getters: unknown,
    root_state: unknown,
    root_getters: {
        "timeperiod/first_date": DateTime;
        "timeperiod/last_date": DateTime;
    },
): Iteration[] => {
    return state.lvl1_iterations.filter((iteration) =>
        isIterationBetweenFirstDateAndLastDate(
            iteration,
            root_getters["timeperiod/first_date"],
            root_getters["timeperiod/last_date"],
        ),
    );
};

export const lvl2_iterations_to_display = (
    state: IterationsState,
    getters: unknown,
    root_state: unknown,
    root_getters: {
        "timeperiod/first_date": DateTime;
        "timeperiod/last_date": DateTime;
    },
): Iteration[] => {
    return state.lvl2_iterations.filter((iteration) =>
        isIterationBetweenFirstDateAndLastDate(
            iteration,
            root_getters["timeperiod/first_date"],
            root_getters["timeperiod/last_date"],
        ),
    );
};

function isIterationBetweenFirstDateAndLastDate(
    iteration: Iteration,
    first_date: DateTime,
    last_date: DateTime,
): boolean {
    return first_date <= iteration.start && iteration.end <= last_date;
}

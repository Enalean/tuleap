/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

export const has_error = (state) => state.error_message !== null;

export const has_success_message = (state) => state.success_message !== null;

import { formatMinutes } from "../../../time-formatters.js";

export function get_formatted_total_sum(state) {
    let sum = getTotalSum(state);
    return formatMinutes(sum);
}

export function is_sum_of_times_equals_zero(state) {
    return getTotalSum(state) === 0;
}

export const is_tracker_total_sum_equals_zero = (state) => (time_per_user) => {
    return getTotalSumPerUser(state, time_per_user) === 0;
};

export const get_formatted_time = (state) => (times) => {
    const minutes = getTotalSumPerUser(state, times.time_per_user);

    return formatMinutes(minutes);
};

export const can_results_be_displayed = (state) =>
    !state.is_loading && state.error_message === null;

function getTotalSum(state) {
    return state.trackers_times.reduce(
        (sum, { time_per_user }) => getTotalSumPerUser(state, time_per_user) + sum,
        0,
    );
}

function getTotalSumPerUser(state, time_per_user) {
    let minutes = 0;
    if (time_per_user.length > 0) {
        if (state.selected_user) {
            time_per_user.forEach((time) => {
                if (time.user_id === parseInt(state.selected_user, 10)) {
                    minutes = minutes + time.minutes;
                }
            });
        } else {
            time_per_user.forEach((time) => {
                minutes = minutes + time.minutes;
            });
        }
    }
    return minutes;
}

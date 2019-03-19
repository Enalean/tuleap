/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

export const has_error = state => state.error_message !== null;

export const has_success_message = state => state.success_message !== null;

import { formatMinutes } from "../../../time-formatters.js";

export function get_formatted_total_sum(state) {
    let sum = 0;
    state.trackers_times.forEach(function(tracker) {
        sum = sum + tracker.minutes;
    });

    return formatMinutes(sum);
}

export const get_formatted_time = () => time => {
    return formatMinutes(time.minutes);
};

export const can_results_be_displayed = state => !state.is_loading && state.error_message === null;

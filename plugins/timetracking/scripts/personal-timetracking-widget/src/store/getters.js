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

import { formatMinutes } from "@tuleap/plugin-timetracking-time-formatters";

export function get_formatted_total_sum(state) {
    const sum = [].concat(...state.times).reduce((sum, { minutes }) => minutes + sum, 0);
    return formatMinutes(sum);
}

export const get_formatted_aggregated_time = () => (times) => {
    const minutes = times.reduce((sum, { minutes }) => minutes + sum, 0);
    return formatMinutes(minutes);
};

export const has_rest_error = (state) => state.error_message !== "";

export const can_results_be_displayed = (state) => state.is_loaded && state.error_message === "";

export const can_load_more = (state) => state.pagination_offset < state.total_times;

export const current_artifact = (state) => {
    if (state.current_times.length === 0) {
        return;
    }
    return state.current_times[0].artifact;
};

export const current_project = (state) => {
    if (state.current_times.length === 0) {
        return;
    }
    return state.current_times[0].project;
};

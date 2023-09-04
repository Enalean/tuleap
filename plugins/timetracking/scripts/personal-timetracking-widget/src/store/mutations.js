/*
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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
import { sortTimesChronologically } from "../../../time-formatters";
import { SUCCESS_TYPE } from "../../../constants.js";

export default {
    toggleReadingMode(state) {
        state.reading_mode = !state.reading_mode;
    },

    setParametersForNewQuery(state, [start_date, end_date]) {
        state.start_date = start_date;
        state.end_date = end_date;
        state.reading_mode = !state.reading_mode;
        state.times = [];
        state.pagination_offset = 0;
    },

    setCurrentTimes(state, times) {
        state.current_times = sortTimesChronologically(times);
    },

    loadAChunkOfTimes(state, [times, total]) {
        state.times = state.times.concat(Object.values(times));
        state.pagination_offset += state.pagination_limit;
        state.total_times = total;
        state.is_loaded = true;
    },

    resetTimes(state) {
        state.is_loading = true;
        state.pagination_offset = 0;
        state.times = [];
        state.is_add_mode = false;
    },

    initUserId(state, user_id) {
        state.user_id = user_id;
    },

    initUserLocale(state, user_locale) {
        state.user_locale = user_locale.replace(/_/g, "-");
    },

    setAddMode(state, is_add_mode) {
        state.is_add_mode = is_add_mode;
        if (
            state.is_add_mode === false ||
            (state.is_add_mode === true && state.rest_feedback.type === SUCCESS_TYPE)
        ) {
            state.rest_feedback.message = "";
            state.rest_feedback.type = "";
        }
    },

    replaceInCurrentTimes(state, [time, feedback_message]) {
        const time_to_update_index = state.current_times.findIndex(
            (current_time) => current_time.id === time.id,
        );
        state.current_times[time_to_update_index] = time;
        state.current_times = sortTimesChronologically(state.current_times);
        state.rest_feedback.message = feedback_message;
        state.rest_feedback.type = SUCCESS_TYPE;
    },

    deleteInCurrentTimes(state, [time_id, feedback_message]) {
        const void_times = [
            {
                artifact: state.current_times[0].artifact,
                project: state.current_times[0].project,
                minutes: null,
            },
        ];
        const time_to_delete_index = state.current_times.findIndex(
            (current_time) => current_time.id === time_id,
        );

        state.current_times.splice(time_to_delete_index, 1);
        if (state.current_times.length === 0) {
            state.current_times = void_times;
        }
        state.rest_feedback.message = feedback_message;
        state.rest_feedback.type = SUCCESS_TYPE;
    },

    resetErrorMessage(state) {
        state.error_message = "";
    },

    setErrorMessage(state, error_message) {
        state.error_message = error_message;
    },

    pushCurrentTimes(state, [times, feedback_message]) {
        if (state.current_times.length === 1 && !state.current_times[0].minutes) {
            state.current_times = [];
        }
        state.current_times = state.current_times.concat(Object.values(times));
        state.current_times = sortTimesChronologically(state.current_times);
        state.is_add_mode = false;
        state.rest_feedback.message = feedback_message;
        state.rest_feedback.type = SUCCESS_TYPE;
    },

    setIsLoading(state, isLoading) {
        state.is_loading = isLoading;
    },

    setRestFeedback(state, [message, type]) {
        state.rest_feedback.message = message;
        state.rest_feedback.type = type;
    },
};

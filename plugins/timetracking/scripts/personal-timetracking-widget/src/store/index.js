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

import { defineStore } from "pinia";
import { formatMinutes } from "@tuleap/plugin-timetracking-time-formatters";
import {
    addTime as addTimeQuerrier,
    deleteTime as deleteTimeQuerrier,
    getTrackedTimes,
    updateTime as updateTimeQuerrier,
} from "../api/rest-querier";
import {
    ERROR_OCCURRED,
    REST_FEEDBACK_ADD,
    REST_FEEDBACK_DELETE,
    REST_FEEDBACK_EDIT,
    SUCCESS_TYPE,
} from "@tuleap/plugin-timetracking-constants";
import { updateEvent } from "../TimetrackingEvents";

export const usePersonalTimetrackingWidgetStore = defineStore("root", {
    state: () => {
        return {
            user_id: null,
            start_date: a_week_ago.toISOString().split("T")[0],
            end_date: new Date().toISOString().split("T")[0],
            reading_mode: true,
            total_times: 0,
            user_locale: null,
            pagination_offset: 0,
            pagination_limit: 50,
            is_loaded: false,
            times: [],
            error_message: "",
            current_times: [],
            is_add_mode: false,
            rest_feedback: {
                message: null,
                type: null,
            },
            is_loading: false,
        };
    },
    getters: {
        get_formatted_total_sum(state) {
            const sum = [].concat(...state.times).reduce((sum, { minutes }) => minutes + sum, 0);
            return formatMinutes(sum);
        },
        get_formatted_aggregated_time: () => (times) => {
            const minutes = times.reduce((sum, { minutes }) => minutes + sum, 0);
            return formatMinutes(minutes);
        },
        has_rest_error: (state) => state.error_message !== "",
        can_results_be_displayed: (state) => state.is_loaded && state.error_message === "",
        can_load_more: (state) => state.pagination_offset < state.total_times,
    },
    actions: {
        setDatesAndReload([start_date, end_date]) {
            this.setParametersForNewQuery([start_date, end_date]);
            return this.loadFirstBatchOfTimes();
        },
        async getTimes() {
            try {
                this.resetErrorMessage();
                const { times, total } = await getTrackedTimes(
                    this.user_id,
                    this.start_date,
                    this.end_date,
                    this.pagination_limit,
                    this.pagination_offset,
                );
                return this.loadAChunkOfTimes([times, total]);
            } catch (error) {
                return this.showErrorMessage(error);
            }
        },
        async addTime([date, artifact, time_value, step]) {
            try {
                const response = await addTimeQuerrier(date, artifact, time_value, step);
                this.pushCurrentTimes([[response], REST_FEEDBACK_ADD]);
                updateEvent();
                return this.loadFirstBatchOfTimes();
            } catch (rest_error) {
                return this.showRestError(rest_error);
            }
        },
        async updateTime([date, time_id, time_value, step]) {
            try {
                const response = await updateTimeQuerrier(date, time_id, time_value, step);
                this.replaceInCurrentTimes([response, REST_FEEDBACK_EDIT]);
                updateEvent();
                return this.loadFirstBatchOfTimes();
            } catch (rest_error) {
                return this.showRestError(rest_error);
            }
        },
        async deleteTime(time_id) {
            try {
                await deleteTimeQuerrier(time_id);
                this.deleteInCurrentTimes([time_id, REST_FEEDBACK_DELETE]);
                updateEvent();
                return this.loadFirstBatchOfTimes();
            } catch (rest_error) {
                return this.showRestError(rest_error);
            }
        },
        async loadFirstBatchOfTimes() {
            this.setIsLoading(true);
            await this.getTimes();
            this.setIsLoading(false);
        },
        async reloadTimes() {
            this.resetTimes();
            await this.getTimes();
            this.setIsLoading(false);
        },
        async showErrorMessage(rest_error) {
            try {
                const { error } = await rest_error.response.json();
                this.setErrorMessage(error.code + " " + error.message);
            } catch (error) {
                this.setErrorMessage(ERROR_OCCURRED);
            }
        },
        async showRestError(rest_error) {
            try {
                const { error } = await rest_error.response.json();
                return this.setRestFeedback([error.code + " " + error.message, "danger"]);
            } catch (error) {
                return this.setRestFeedback([ERROR_OCCURRED, "danger"]);
            }
        },
        toggleReadingMode() {
            this.reading_mode = !this.reading_mode;
        },
        setParametersForNewQuery([start_date, end_date]) {
            this.start_date = start_date;
            this.end_date = end_date;
            this.reading_mode = !this.reading_mode;
            this.times = [];
            this.pagination_offset = 0;
        },
        setCurrentTimes(times) {
            this.current_times = times.sort((a, b) => {
                return new Date(b.date) - new Date(a.date);
            });
        },
        loadAChunkOfTimes([times, total]) {
            this.times = this.times.concat(Object.values(times));
            this.pagination_offset += this.pagination_limit;
            this.total_times = total;
            this.is_loaded = true;
        },
        resetTimes() {
            this.is_loading = true;
            this.pagination_offset = 0;
            this.times = [];
            this.is_add_mode = false;
        },
        initUserId(user_id) {
            this.user_id = user_id;
        },
        initUserLocale(user_locale) {
            this.user_locale = user_locale.replace(/_/g, "-");
        },
        setAddMode(is_add_mode) {
            this.is_add_mode = is_add_mode;
            if (
                this.is_add_mode === false ||
                (this.is_add_mode === true && this.rest_feedback.type === SUCCESS_TYPE)
            ) {
                this.rest_feedback.message = "";
                this.rest_feedback.type = "";
            }
        },
        replaceInCurrentTimes([time, feedback_message]) {
            const time_to_update_index = this.current_times.findIndex(
                (current_time) => current_time.id === time.id,
            );
            this.current_times[time_to_update_index] = time;
            this.current_times = this.current_times.sort((a, b) => {
                return new Date(b.date) - new Date(a.date);
            });
            this.rest_feedback.message = feedback_message;
            this.rest_feedback.type = SUCCESS_TYPE;
        },
        deleteInCurrentTimes([time_id, feedback_message]) {
            const time_to_delete_index = this.current_times.findIndex(
                (current_time) => current_time.id === time_id,
            );
            this.current_times.splice(time_to_delete_index, 1);
            this.rest_feedback.message = feedback_message;
            this.rest_feedback.type = SUCCESS_TYPE;
        },
        resetErrorMessage() {
            this.error_message = "";
        },
        setErrorMessage(error_message) {
            this.error_message = error_message;
        },
        pushCurrentTimes([times, feedback_message]) {
            this.current_times = this.current_times.concat(Object.values(times));
            this.current_times = this.current_times.sort((a, b) => {
                return new Date(b.date) - new Date(a.date);
            });
            this.is_add_mode = false;
            this.rest_feedback.message = feedback_message;
            this.rest_feedback.type = SUCCESS_TYPE;
        },
        setIsLoading(isLoading) {
            this.is_loading = isLoading;
        },
        setRestFeedback([message, type]) {
            this.rest_feedback.message = message;
            this.rest_feedback.type = type;
        },
    },
});

const a_week_ago = new Date();
a_week_ago.setDate(a_week_ago.getDate() - 7);

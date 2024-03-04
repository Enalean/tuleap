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
import {
    formatDatetimeToYearMonthDay,
    formatMinutes,
} from "@tuleap/plugin-timetracking-time-formatters";
import { postTime, delTime, getTrackedTimes, putTime } from "../api/rest-querier";
import {
    REST_FEEDBACK_ADD,
    REST_FEEDBACK_DELETE,
    REST_FEEDBACK_EDIT,
    SUCCESS_TYPE,
} from "@tuleap/plugin-timetracking-constants";
import { updateEvent } from "../TimetrackingEvents";
import type { PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";

const a_week_ago: Date = new Date();
a_week_ago.setDate(a_week_ago.getDate() - 7);

interface State {
    user_id: number;
    start_date: string;
    end_date: string;
    reading_mode: boolean;
    total_times: number;
    user_locale: string;
    pagination_offset: number;
    pagination_limit: number;
    is_loaded: boolean;
    times: PersonalTime[][];
    error_message: string;
    current_times: PersonalTime[];
    is_add_mode: boolean;
    rest_feedback: {
        message: string;
        type: string;
    };
    is_loading: boolean;
}

export const usePersonalTimetrackingWidgetStore = defineStore("root", {
    state: (): State => {
        return {
            user_id: 0,
            start_date: formatDatetimeToYearMonthDay(a_week_ago),
            end_date: formatDatetimeToYearMonthDay(new Date()),
            reading_mode: true,
            total_times: 0,
            user_locale: "",
            pagination_offset: 0,
            pagination_limit: 50,
            is_loaded: false,
            times: [],
            error_message: "",
            current_times: [],
            is_add_mode: false,
            rest_feedback: {
                message: "",
                type: "",
            },
            is_loading: false,
        };
    },
    getters: {
        get_formatted_total_sum(state: State): string {
            const sum = state.times.flat().reduce((sum, { minutes }) => minutes + sum, 0);
            return formatMinutes(sum);
        },
        get_formatted_aggregated_time:
            () =>
            (times: PersonalTime[]): string => {
                const minutes = times.reduce((sum, { minutes }) => minutes + sum, 0);
                return formatMinutes(minutes);
            },
        has_rest_error: (state): boolean => state.error_message !== "",
        can_results_be_displayed: (state) => state.is_loaded && state.error_message === "",
        can_load_more: (state): boolean => state.pagination_offset < state.total_times,
    },
    actions: {
        setDatesAndReload(start_date: string, end_date: string): void {
            this.setParametersForNewQuery(start_date, end_date);
            this.loadFirstBatchOfTimes();
        },
        getTimes() {
            this.resetErrorMessage();

            getTrackedTimes(
                this.user_id,
                this.start_date,
                this.end_date,
                this.pagination_limit,
                this.pagination_offset,
            ).match(
                (total_times) => this.loadAChunkOfTimes(total_times.times, total_times.total),
                (fault) => {
                    this.setErrorMessage(String(fault));
                },
            );
        },
        addTime(date: string, artifact: number, time_value: string, step: string): void {
            postTime(date, artifact, time_value, step).match(
                (personal_time) => {
                    this.pushCurrentTimes([personal_time], REST_FEEDBACK_ADD);
                    updateEvent();

                    this.loadFirstBatchOfTimes();
                },
                (fault) => {
                    this.setRestFeedback(String(fault), "danger");
                },
            );
        },
        updateTime(date: string, time_id: number, time_value: string, step: string): void {
            putTime(date, time_id, time_value, step).match(
                (personal_time) => {
                    this.replaceInCurrentTimes(personal_time, REST_FEEDBACK_EDIT);
                    updateEvent();
                    this.loadFirstBatchOfTimes();
                },
                (fault) => {
                    this.setRestFeedback(String(fault), "danger");
                },
            );
        },
        deleteTime(time_id: number): void {
            delTime(time_id).match(
                () => {
                    this.deleteInCurrentTimes(time_id, REST_FEEDBACK_DELETE);
                    updateEvent();
                    this.loadFirstBatchOfTimes();
                },
                (fault) => {
                    this.setRestFeedback(String(fault), "danger");
                },
            );
        },
        loadFirstBatchOfTimes(): void {
            this.setIsLoading(true);
            this.getTimes();
            this.setIsLoading(false);
        },
        reloadTimes(): void {
            this.resetTimes();
            this.getTimes();
            this.setIsLoading(false);
        },
        toggleReadingMode(): void {
            this.reading_mode = !this.reading_mode;
        },
        setParametersForNewQuery(start_date: string, end_date: string): void {
            this.start_date = start_date;
            this.end_date = end_date;
            this.reading_mode = !this.reading_mode;
            this.times = [];
            this.pagination_offset = 0;
        },
        setCurrentTimes(times: PersonalTime[]): void {
            this.sortTimes(times);
            this.current_times = times;
        },
        loadAChunkOfTimes(times: PersonalTime[], total: number): void {
            this.times = this.times.concat(Object.values(times));
            this.pagination_offset += this.pagination_limit;
            this.total_times = total;
            this.is_loaded = true;
        },
        resetTimes(): void {
            this.is_loading = true;
            this.pagination_offset = 0;
            this.times = [];
            this.is_add_mode = false;
        },
        initUserId(user_id: number): void {
            this.user_id = user_id;
        },
        initUserLocale(user_locale: string): void {
            this.user_locale = user_locale.replace(/_/g, "-");
        },
        setAddMode(is_add_mode: boolean): void {
            this.is_add_mode = is_add_mode;
            if (
                !this.is_add_mode ||
                (this.is_add_mode && this.rest_feedback.type === SUCCESS_TYPE)
            ) {
                this.rest_feedback.message = "";
                this.rest_feedback.type = "";
            }
        },
        replaceInCurrentTimes(time: PersonalTime, feedback_message: string): void {
            const time_to_update_index = this.current_times.findIndex(
                (current_time) => current_time.id === time.id,
            );
            this.current_times[time_to_update_index] = time;
            this.sortTimes(this.current_times);
            this.rest_feedback.message = feedback_message;
            this.rest_feedback.type = SUCCESS_TYPE;
        },
        deleteInCurrentTimes(time_id: number, feedback_message: string): void {
            const time_to_delete_index = this.current_times.findIndex(
                (current_time) => current_time.id === time_id,
            );
            this.current_times.splice(time_to_delete_index, 1);
            this.rest_feedback.message = feedback_message;
            this.rest_feedback.type = SUCCESS_TYPE;
        },
        resetErrorMessage(): void {
            this.error_message = "";
        },
        setErrorMessage(error_message: string): void {
            this.error_message = error_message;
        },
        pushCurrentTimes(times: PersonalTime[], feedback_message: string): void {
            this.current_times = this.current_times.concat(Object.values(times));
            this.sortTimes(this.current_times);
            this.is_add_mode = false;
            this.rest_feedback.message = feedback_message;
            this.rest_feedback.type = SUCCESS_TYPE;
        },
        setIsLoading(isLoading: boolean): void {
            this.is_loading = isLoading;
        },
        setRestFeedback(message: string, type: string): void {
            this.rest_feedback.message = message;
            this.rest_feedback.type = type;
        },
        sortTimes(times: PersonalTime[]): void {
            times.sort((a, b) => {
                return new Date(String(b.date)).getTime() - new Date(String(a.date)).getTime();
            });
        },
    },
});

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
import { default_state } from "./state.js";

import {
    getProjectsWithTimetracking,
    getTimesFromReport,
    getTrackersFromReport,
    getTrackersWithTimetracking,
    getTimes,
    saveNewReport,
    setDisplayPreference,
} from "../api/rest-querier.js";
import { ERROR_OCCURRED } from "@tuleap/plugin-timetracking-constants";
import { formatMinutes } from "@tuleap/plugin-timetracking-time-formatters";

const getTotalSumPerUser = (state, time_per_user) => {
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
};

const getTotalSum = (state) => {
    return state.trackers_times.reduce((sum, { time_per_user }) => {
        return getTotalSumPerUser(state, time_per_user) + sum;
    }, 0);
};

/**
 * Define a store for one instance of a TimeTrackingOverviewWidget. It is named after the report of the widget.
 */
export function useOverviewWidgetStore(report_id) {
    return defineStore(`overview/${report_id}`, {
        state: () => ({ ...default_state }),
        getters: {
            has_error: (state) => state.error_message !== null,
            has_success_message: (state) => state.success_message !== null,
            can_results_be_displayed: (state) => !state.is_loading && state.error_message === null,
            get_formatted_total_sum(state) {
                return formatMinutes(getTotalSum(state));
            },
            is_sum_of_times_equals_zero(state) {
                return getTotalSum(state) === 0;
            },
            is_tracker_total_sum_equals_zero(state) {
                return (time_per_user) => {
                    return getTotalSumPerUser(state, time_per_user) === 0;
                };
            },
            get_formatted_time(state) {
                return (times) => {
                    return formatMinutes(getTotalSumPerUser(state, times.time_per_user));
                };
            },
        },
        actions: {
            async initWidgetWithReport() {
                try {
                    this.resetMessages();

                    const report = await getTrackersFromReport(this.report_id);
                    this.setSelectedTrackers(report.trackers);

                    return await this.loadTimes();
                } catch (error) {
                    return this.showRestError(error);
                }
            },

            async getProjects() {
                try {
                    this.resetMessages();
                    const projects = await getProjectsWithTimetracking();
                    return this.setProjects(projects);
                } catch (error) {
                    return this.showRestError(error);
                }
            },

            async saveReport(message) {
                try {
                    this.resetMessages();
                    this.setTrackersIds();
                    const report = await saveNewReport(
                        this.report_id,
                        this.trackers_ids ? this.trackers_ids : [],
                    );
                    this.setSelectedTrackers(report.trackers);
                    this.setSuccessMessage(message);

                    this.setIsReportSave(true);

                    return await this.loadTimes();
                } catch (error) {
                    return this.showRestError(error);
                }
            },

            async getTrackers(project_id) {
                try {
                    this.resetMessages();
                    this.setLoadingTrackers(true);
                    const trackers = await getTrackersWithTimetracking(project_id);
                    this.setTrackers(trackers);
                    return this.setLoadingTrackers(false);
                } catch (error) {
                    return this.showRestError(error);
                }
            },

            async loadTimes() {
                this.setIsLoading(true);

                const times = await this.getTimesWithoutNewParameters();
                this.setTrackersTimes(times);
                this.setIsLoading(false);
            },

            async reloadTimetrackingOverviewTimes() {
                this.setIsLoading(true);
                let times;
                if (this.trackers_ids.length > 0) {
                    times = await this.getTimesWithNewParameters();
                } else {
                    times = await this.getTimesWithoutNewParameters();
                }

                this.setTrackersTimes(times);
                this.setIsLoading(false);
            },

            async loadTimesWithNewParameters() {
                this.setIsLoading(true);
                this.setTrackersIds();

                const times = await this.getTimesWithNewParameters();

                this.toggleReadingMode();
                this.setIsReportSave(false);
                this.setTrackersTimes(times);
                this.setIsLoading(false);
            },

            async setPreference() {
                try {
                    await setDisplayPreference(
                        this.report_id,
                        this.user_id,
                        !this.are_void_trackers_hidden,
                    );
                    return this.toggleDisplayVoidTrackers();
                } catch (rest_error) {
                    return this.showRestError(rest_error);
                }
            },

            async showRestError(rest_error) {
                try {
                    const { error } = await rest_error.response.json();
                    this.setErrorMessage(error.code + " " + error.message);
                } catch (error) {
                    this.setErrorMessage(ERROR_OCCURRED);
                }
            },

            getTimesWithNewParameters() {
                return getTimes(this.report_id, this.trackers_ids, this.start_date, this.end_date);
            },

            getTimesWithoutNewParameters() {
                return getTimesFromReport(this.report_id, this.start_date, this.end_date);
            },

            setSelectedTrackers(trackers) {
                this.selected_trackers = trackers;
            },

            setTrackersTimes(times) {
                this.trackers_times = times;
                this.trackers_times.forEach((time) => {
                    this.setUsers(time);
                });
            },

            setDisplayVoidTrackers(are_void_trackers_hidden) {
                this.are_void_trackers_hidden = are_void_trackers_hidden;
            },

            initUserId(user_id) {
                this.user_id = user_id;
            },

            toggleDisplayVoidTrackers() {
                this.are_void_trackers_hidden = !this.are_void_trackers_hidden;
            },

            setLoadingTrackers(is_loading_trackers) {
                this.is_loading_trackers = is_loading_trackers;
            },

            setIsLoading(is_loading) {
                this.is_loading = is_loading;
            },

            resetMessages() {
                this.error_message = null;
                this.success_message = null;
            },

            setErrorMessage(error_message) {
                this.error_message = error_message;
            },

            setSuccessMessage(success_message) {
                this.success_message = success_message;
            },

            toggleReadingMode() {
                this.reading_mode = !this.reading_mode;
            },

            setProjects(projects) {
                this.projects = projects;
            },

            setStartDate(start_date) {
                this.start_date = start_date;
            },

            setEndDate(end_date) {
                this.end_date = end_date;
            },

            setTrackers(trackers) {
                trackers.forEach((tracker) => {
                    tracker.disabled = Boolean(
                        this.selected_trackers.find(
                            (selected_tracker) => selected_tracker.id === tracker.id,
                        ),
                    );
                });
                this.trackers = trackers;
            },

            setTrackersIds() {
                this.trackers_ids = [];
                this.selected_trackers.forEach((tracker) => {
                    this.trackers_ids.push(tracker.id);
                });
            },

            setSelectedUser(user) {
                this.selected_user = user;
            },

            addSelectedTrackers(tracker_id) {
                this.is_added_tracker = false;
                this.trackers.forEach((tracker) => {
                    if (
                        tracker.id === parseInt(tracker_id, 10) &&
                        !this.selected_trackers.find(
                            (selected_tracker) => selected_tracker.id === tracker.id,
                        )
                    ) {
                        this.selected_trackers.push(tracker);
                        tracker.disabled = true;
                    }
                });
                this.is_added_tracker = true;
            },

            setIsReportSave(is_report_saved) {
                this.is_report_saved = is_report_saved;
            },

            removeSelectedTracker(tracker) {
                this.selected_trackers.splice(this.selected_trackers.indexOf(tracker), 1);
            },

            setReportId(report_id) {
                this.report_id = report_id;
            },
            setUsers(time) {
                if (time.time_per_user.length > 0) {
                    time.time_per_user.reduce(function (users, user_time) {
                        if (!users.find((user) => user.user_id === user_time.user_id)) {
                            const user = {
                                user_name: user_time.user_name,
                                user_id: user_time.user_id,
                            };
                            users.push(user);
                        }
                        return users;
                    }, this.users);
                }
            },
        },
    });
}

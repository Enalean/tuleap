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

import type { StoreDefinition } from "pinia";
import { defineStore } from "pinia";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import type { ProjectReference } from "@tuleap/core-rest-api-types";
import { ERROR_OCCURRED } from "@tuleap/plugin-timetracking-constants";
import { formatMinutes } from "@tuleap/plugin-timetracking-time-formatters";
import type {
    OverviewReportTracker,
    TrackerWithTimes,
    UserTotalTrackerTimes,
} from "@tuleap/plugin-timetracking-rest-api-types";
import { default_state } from "./state";
import type { OverviewWidgetState } from "./state";
import {
    getProjectsWithTimetracking,
    getTimesFromReport,
    getTrackersFromReport,
    getTrackersWithTimetracking,
    getTimes,
    saveNewReport,
    setDisplayPreference,
} from "../api/rest-querier";

export type OverviewWidgetStoreActions = {
    initWidgetWithReport(): Promise<void>;
    getProjects(): Promise<void>;
    saveReport(message: string): Promise<void>;
    getTrackers(project_id: number): Promise<void>;
    loadTimes(): Promise<void>;
    reloadTimetrackingOverviewTimes(): Promise<void>;
    loadTimesWithNewParameters(): Promise<void>;
    setPreference(): Promise<void>;
    showRestError(rest_error: unknown): Promise<void>;
    getTimesWithNewParameters(): Promise<TrackerWithTimes[]>;
    getTimesWithoutNewParameters(): Promise<TrackerWithTimes[]>;
    setSelectedTrackers(trackers: OverviewReportTracker[]): void;
    setTrackersTimes(times: TrackerWithTimes[]): void;
    setDisplayVoidTrackers(are_void_trackers_hidden: boolean): void;
    initUserId(user_id: number): void;
    toggleDisplayVoidTrackers(): void;
    setLoadingTrackers(is_loading_trackers: boolean): void;
    setIsLoading(is_loading: boolean): void;
    resetMessages(): void;
    setErrorMessage(error_message: string): void;
    setSuccessMessage(success_message: string): void;
    toggleReadingMode(): void;
    setProjects(projects: ProjectReference[]): void;
    setStartDate(start_date: string): void;
    setEndDate(end_date: string): void;
    setTrackers(trackers: OverviewReportTracker[]): void;
    setTrackersIds(): void;
    setSelectedUserId(user: number): void;
    addSelectedTrackers(tracker_id: number): void;
    setIsReportSave(is_report_saved: boolean): void;
    removeSelectedTracker(tracker: OverviewReportTracker): void;
    setReportId(report_id: number): void;
    setUsers(time: TrackerWithTimes): void;
};

export type OverviewWidgetStoreGetters = {
    has_error(state: OverviewWidgetState): boolean;
    has_success_message(state: OverviewWidgetState): boolean;
    can_results_be_displayed(state: OverviewWidgetState): boolean;
    get_formatted_total_sum(state: OverviewWidgetState): string;
    is_sum_of_times_equals_zero(state: OverviewWidgetState): boolean;
    is_tracker_total_sum_equals_zero(
        state: OverviewWidgetState,
    ): (time_per_user: UserTotalTrackerTimes[]) => boolean;
    get_formatted_time(state: OverviewWidgetState): (times: TrackerWithTimes) => string;
};

export type OverviewWidgetStoreDefinition = StoreDefinition<
    string,
    OverviewWidgetState,
    OverviewWidgetStoreGetters,
    OverviewWidgetStoreActions
>;

const getTotalSumPerUser = (
    state: OverviewWidgetState,
    time_per_user: UserTotalTrackerTimes[],
): number => {
    let minutes = 0;
    if (time_per_user.length > 0) {
        if (state.selected_user_id) {
            time_per_user.forEach((time): void => {
                if (time.user_id === state.selected_user_id) {
                    minutes = minutes + time.minutes;
                }
            });
        } else {
            time_per_user.forEach((time): void => {
                minutes = minutes + time.minutes;
            });
        }
    }
    return minutes;
};

const getTotalSum = (state: OverviewWidgetState): number => {
    return state.trackers_times.reduce((sum: number, { time_per_user }) => {
        return getTotalSumPerUser(state, time_per_user) + sum;
    }, 0);
};

/**
 * Define a store for one instance of a TimeTrackingOverviewWidget. It is named after the report of the widget.
 */
export function useOverviewWidgetStore(report_id: number): OverviewWidgetStoreDefinition {
    return defineStore(`overview/${report_id}`, {
        state: (): OverviewWidgetState => ({ ...default_state }),
        getters: {
            has_error: (state: OverviewWidgetState): boolean => state.error_message !== null,
            has_success_message: (state: OverviewWidgetState): boolean =>
                state.success_message !== null,
            can_results_be_displayed: (state: OverviewWidgetState): boolean =>
                !state.is_loading && state.error_message === null,
            get_formatted_total_sum(state: OverviewWidgetState): string {
                return formatMinutes(getTotalSum(state));
            },
            is_sum_of_times_equals_zero(state: OverviewWidgetState): boolean {
                return getTotalSum(state) === 0;
            },
            is_tracker_total_sum_equals_zero(
                state: OverviewWidgetState,
            ): (time_per_user: UserTotalTrackerTimes[]) => boolean {
                return (time_per_user: UserTotalTrackerTimes[]): boolean => {
                    return getTotalSumPerUser(state, time_per_user) === 0;
                };
            },
            get_formatted_time(state: OverviewWidgetState): (times: TrackerWithTimes) => string {
                return (times: TrackerWithTimes) => {
                    return formatMinutes(getTotalSumPerUser(state, times.time_per_user));
                };
            },
        },
        actions: {
            async initWidgetWithReport(): Promise<void> {
                try {
                    this.resetMessages();

                    const report = await getTrackersFromReport(this.report_id);
                    this.setSelectedTrackers(report.trackers);

                    return await this.loadTimes();
                } catch (error) {
                    return this.showRestError(error);
                }
            },

            async getProjects(): Promise<void> {
                try {
                    this.resetMessages();
                    const projects = await getProjectsWithTimetracking();
                    return this.setProjects(projects);
                } catch (error) {
                    return this.showRestError(error);
                }
            },

            async saveReport(message: string): Promise<void> {
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

            async getTrackers(project_id: number): Promise<void> {
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

            async loadTimes(): Promise<void> {
                this.setIsLoading(true);

                const times = await this.getTimesWithoutNewParameters();
                this.setTrackersTimes(times);
                this.setIsLoading(false);
            },

            async reloadTimetrackingOverviewTimes(): Promise<void> {
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

            async loadTimesWithNewParameters(): Promise<void> {
                this.setIsLoading(true);
                this.setTrackersIds();

                const times = await this.getTimesWithNewParameters();

                this.toggleReadingMode();
                this.setIsReportSave(false);
                this.setTrackersTimes(times);
                this.setIsLoading(false);
            },

            async setPreference(): Promise<void> {
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

            async showRestError(rest_error: unknown): Promise<void> {
                if (!(rest_error instanceof FetchWrapperError)) {
                    this.setErrorMessage(ERROR_OCCURRED);

                    return;
                }

                try {
                    const { error } = await rest_error.response.json();
                    this.setErrorMessage(error.code + " " + error.message);
                } catch (error) {
                    this.setErrorMessage(ERROR_OCCURRED);
                }
            },

            getTimesWithNewParameters(): Promise<TrackerWithTimes[]> {
                return getTimes(this.report_id, this.trackers_ids, this.start_date, this.end_date);
            },

            getTimesWithoutNewParameters(): Promise<TrackerWithTimes[]> {
                return getTimesFromReport(this.report_id, this.start_date, this.end_date);
            },

            setSelectedTrackers(trackers: OverviewReportTracker[]): void {
                this.selected_trackers = trackers;
            },

            setTrackersTimes(times: TrackerWithTimes[]): void {
                this.trackers_times = times;
                this.trackers_times.forEach((time): void => {
                    this.setUsers(time);
                });
            },

            setDisplayVoidTrackers(are_void_trackers_hidden: boolean): void {
                this.are_void_trackers_hidden = are_void_trackers_hidden;
            },

            initUserId(user_id: number): void {
                this.user_id = user_id;
            },

            toggleDisplayVoidTrackers(): void {
                this.are_void_trackers_hidden = !this.are_void_trackers_hidden;
            },

            setLoadingTrackers(is_loading_trackers: boolean): void {
                this.is_loading_trackers = is_loading_trackers;
            },

            setIsLoading(is_loading: boolean): void {
                this.is_loading = is_loading;
            },

            resetMessages(): void {
                this.error_message = null;
                this.success_message = null;
            },

            setErrorMessage(error_message: string): void {
                this.error_message = error_message;
            },

            setSuccessMessage(success_message: string): void {
                this.success_message = success_message;
            },

            toggleReadingMode(): void {
                this.reading_mode = !this.reading_mode;
            },

            setProjects(projects: ProjectReference[]): void {
                this.projects = projects;
            },

            setStartDate(start_date: string): void {
                this.start_date = start_date;
            },

            setEndDate(end_date: string): void {
                this.end_date = end_date;
            },

            setTrackers(trackers: OverviewReportTracker[]): void {
                this.trackers = trackers.map((tracker) => {
                    const disabled = Boolean(
                        this.selected_trackers.find(
                            (selected_tracker) => selected_tracker.id === tracker.id,
                        ),
                    );

                    return {
                        ...tracker,
                        disabled,
                    };
                });
            },

            setTrackersIds(): void {
                this.trackers_ids = [];
                this.selected_trackers.forEach((tracker) => {
                    this.trackers_ids.push(tracker.id);
                });
            },

            setSelectedUserId(user: number): void {
                this.selected_user_id = user;
            },

            addSelectedTrackers(tracker_id: number): void {
                this.is_added_tracker = false;
                this.trackers.forEach((tracker): void => {
                    if (
                        tracker.id === tracker_id &&
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

            setIsReportSave(is_report_saved: boolean): void {
                this.is_report_saved = is_report_saved;
            },

            removeSelectedTracker(tracker: OverviewReportTracker): void {
                this.selected_trackers.splice(this.selected_trackers.indexOf(tracker), 1);
            },

            setReportId(report_id: number): void {
                this.report_id = report_id;
            },
            setUsers(time: TrackerWithTimes): void {
                if (time.time_per_user.length === 0) {
                    return;
                }

                time.time_per_user.reduce(function (users, user_time) {
                    if (!users.find((user) => user.user_id === user_time.user_id)) {
                        users.push({
                            user_id: user_time.user_id,
                            user_name: user_time.user_name,
                        });
                    }
                    return users;
                }, this.users);
            },
        },
    });
}

/*
 * Copyright Enalean (c) 2019 - present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import { describe, beforeEach, afterEach, it, expect } from "@jest/globals";
import { setActivePinia, createPinia } from "pinia";
import { useOverviewWidgetTestStore } from "../../tests/helpers/pinia-test-store.js";

describe("Store mutations", () => {
    let store;

    beforeEach(() => {
        setActivePinia(createPinia());
        store = useOverviewWidgetTestStore();
    });

    afterEach(() => {
        store.$reset();
    });

    describe("Given a widget with state initialisation", () => {
        it("When selected trackers are set, state must change too", () => {
            const trackers = [{ id: 1, label: "timetracking_tracker" }];

            store.setSelectedTrackers(trackers);
            expect(store.selected_trackers).toStrictEqual(trackers);
        });

        it("When projects are set, state must change too", () => {
            const projects = [
                { id: 765, label: "timetracking" },
                { id: 239, label: "projectTest" },
            ];

            store.setProjects(projects);
            expect(store.projects).toStrictEqual(projects);
        });

        it("When times are set, times and user must change on state", () => {
            const times = [
                {
                    id: "16",
                    label: "tracker",
                    project: {},
                    uri: "",
                    time_per_user: [
                        {
                            user_name: "user",
                            user_id: 102,
                            minutes: 60,
                        },
                    ],
                },
            ];
            store.setTrackersTimes(times);

            expect(store.trackers_times).toStrictEqual(times);
            expect(store.users).toStrictEqual([{ user_name: "user", user_id: 102 }]);
        });

        it("When we put new dates, state must change too", () => {
            store.setStartDate("2018-01-01");
            store.setEndDate("2018-02-02");

            expect(store.start_date).toBe("2018-01-01");
            expect(store.end_date).toBe("2018-02-02");
        });

        it("When we set display void trackers, state must change too", () => {
            store.setDisplayVoidTrackers(false);
            expect(store.are_void_trackers_hidden).toBe(false);
        });

        it("When we init user id, state must change too", () => {
            store.initUserId(102);
            expect(store.user_id).toBe(102);
        });

        it("When we toggle display void trackers, state must change too", () => {
            store.setDisplayVoidTrackers(false);
            store.toggleDisplayVoidTrackers();
            expect(store.are_void_trackers_hidden).toBe(true);
        });

        it("When we set loading trackers, state must change too", () => {
            store.setLoadingTrackers(true);
            expect(store.is_loading_trackers).toBe(true);
        });

        it("When we set is_loading, state must change too", () => {
            store.setIsLoading(true);
            expect(store.is_loading).toBe(true);
        });

        it("When we set error message, state must change too", () => {
            store.setErrorMessage("error");
            expect(store.error_message).toBe("error");
        });

        it("When we set success message, state must change too", () => {
            store.setSuccessMessage("success");
            expect(store.success_message).toBe("success");
        });

        it("When we reset messages, state must change too", () => {
            store.setSuccessMessage("success");
            store.setErrorMessage("error");
            store.resetMessages();

            expect(store.success_message).toBeNull();
        });

        it("When we toggle reading_mode, state must change too", () => {
            store.toggleReadingMode();
            expect(store.reading_mode).toBe(false);
        });

        it("When trackers id are set, state must change too", () => {
            const trackers = [
                { id: 1, label: "timetracking_tracker" },
                { id: 2, label: "support_tracker" },
            ];

            store.setSelectedTrackers(trackers);
            store.setTrackersIds();
            expect(store.trackers_ids).toStrictEqual([1, 2]);
        });

        it("When we set selected user, state must change too", () => {
            const user = {
                user_name: "user",
                user_id: 102,
                minutes: 60,
            };

            store.setSelectedUser(user);
            expect(store.selected_user).toStrictEqual(user);
        });

        it("When we is_report_saved, state must change too", () => {
            store.setIsReportSave(true);
            expect(store.is_report_saved).toBe(true);
        });

        it("When we remove a selected tracker, state must change too", () => {
            const selected_tracker = [{ id: 1, label: "timetracking_tracker", disabled: true }];
            store.setSelectedTrackers(selected_tracker);
            store.removeSelectedTracker(selected_tracker);
            expect(store.selected_trackers).toStrictEqual([]);
        });

        it("When we set report id, state must change too", () => {
            store.setReportId(12);
            expect(store.report_id).toBe(12);
        });

        describe("When trackers are added, state must change too", () => {
            beforeEach(() => {
                const trackers = [
                    { id: 1, label: "timetracking_tracker" },
                    { id: 2, label: "support_tracker" },
                    { id: 3, label: "task_tracker" },
                ];

                store.setTrackers(trackers);
            });

            it("When we add already existing selected trackers, nothing should change", () => {
                const selected_tracker = [{ id: 1, label: "timetracking_tracker", disabled: true }];
                const tracker_id = 1;

                store.addSelectedTrackers(tracker_id);
                expect(store.selected_trackers).toStrictEqual(selected_tracker);
            });
        });

        describe("When trackers are set, state must change too", () => {
            beforeEach(() => {
                const trackers = [];
                store.setSelectedTrackers(trackers);
            });

            it("When no selected_trackers, no tracker are disabled", () => {
                const trackers = [
                    { id: 1, label: "timetracking_tracker" },
                    { id: 2, label: "support_tracker" },
                    { id: 3, label: "task_tracker" },
                ];

                const tracker_temoin = [
                    { id: 1, label: "timetracking_tracker", disabled: false },
                    { id: 2, label: "support_tracker", disabled: false },
                    { id: 3, label: "task_tracker", disabled: false },
                ];

                store.setTrackers(trackers);
                expect(store.trackers).toStrictEqual(tracker_temoin);
            });

            it("When selected_trackers, tracker identic is disabled", () => {
                store.setSelectedTrackers([{ id: 1, label: "timetracking_tracker" }]);
                const trackers = [
                    { id: 1, label: "timetracking_tracker" },
                    { id: 2, label: "support_tracker" },
                    { id: 3, label: "task_tracker" },
                ];

                const tracker_temoin = [
                    { id: 1, label: "timetracking_tracker", disabled: true },
                    { id: 2, label: "support_tracker", disabled: false },
                    { id: 3, label: "task_tracker", disabled: false },
                ];

                store.setTrackers(trackers);
                expect(store.trackers).toStrictEqual(tracker_temoin);
            });
        });
    });
});

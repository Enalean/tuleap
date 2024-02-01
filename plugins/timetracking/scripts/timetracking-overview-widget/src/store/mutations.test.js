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

import mutations from "./mutations.js";
import initial_state from "./state.js";

describe("Store mutations", () => {
    let state;
    beforeEach(() => {
        state = { ...initial_state };
    });
    describe("Given a widget with state initialisation", () => {
        it("When selected trackers are set, state must change too", () => {
            let trackers = [{ id: 1, label: "timetracking_tracker" }];

            mutations.setSelectedTrackers(state, trackers);
            expect(state.selected_trackers).toEqual(trackers);
        });

        it("When projects are set, state must change too", () => {
            const projects = [
                { id: 765, label: "timetracking" },
                { id: 239, label: "projectTest" },
            ];

            mutations.setProjects(state, projects);
            expect(state.projects).toEqual(projects);
        });

        it("When times are set, times and user must change on state", () => {
            let times = [
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
            mutations.setTrackersTimes(state, times);
            expect(state.trackers_times).toEqual(times);
            expect(state.users).toEqual([{ user_name: "user", user_id: 102 }]);
        });

        it("When we put new dates, state must change too", () => {
            mutations.setStartDate(state, "2018-01-01");
            mutations.setEndDate(state, "2018-02-02");
            expect(state.start_date).toBe("2018-01-01");
            expect(state.end_date).toBe("2018-02-02");
        });

        it("When we set display void trackers, state must change too", () => {
            mutations.setDisplayVoidTrackers(state, false);
            expect(state.are_void_trackers_hidden).toBe(false);
        });

        it("When we init user id, state must change too", () => {
            mutations.initUserId(state, 102);
            expect(state.user_id).toBe(102);
        });

        it("When we toggle display void trackers, state must change too", () => {
            mutations.setDisplayVoidTrackers(state, false);
            mutations.toggleDisplayVoidTrackers(state);
            expect(state.are_void_trackers_hidden).toBe(true);
        });

        it("When we set loading trackers, state must change too", () => {
            mutations.setLoadingTrackers(state, true);
            expect(state.is_loading_trackers).toBe(true);
        });

        it("When we set is_loading, state must change too", () => {
            mutations.setIsLoading(state, true);
            expect(state.is_loading).toBe(true);
        });

        it("When we set error message, state must change too", () => {
            mutations.setErrorMessage(state, "error");
            expect(state.error_message).toBe("error");
        });

        it("When we set success message, state must change too", () => {
            mutations.setSuccessMessage(state, "success");
            expect(state.success_message).toBe("success");
        });

        it("When we reset messages, state must change too", () => {
            mutations.setSuccessMessage(state, "success");
            mutations.setErrorMessage(state, "error");
            mutations.resetMessages(state);

            expect(state.success_message).toBeNull();
        });

        it("When we toggle reading_mode, state must change too", () => {
            mutations.toggleReadingMode(state);
            expect(state.reading_mode).toBe(false);
        });

        it("When trackers id are set, state must change too", () => {
            let trackers = [
                { id: 1, label: "timetracking_tracker" },
                { id: 2, label: "support_tracker" },
            ];

            mutations.setSelectedTrackers(state, trackers);
            mutations.setTrackersIds(state);
            expect(state.trackers_ids).toEqual([1, 2]);
        });

        it("When we set selected user, state must change too", () => {
            const user = {
                user_name: "user",
                user_id: 102,
                minutes: 60,
            };

            mutations.setSelectedUser(state, user);
            expect(state.selected_user).toEqual(user);
        });

        it("When we is_report_saved, state must change too", () => {
            mutations.setIsReportSave(state, true);
            expect(state.is_report_saved).toBe(true);
        });

        it("When we remove a selected tracker, state must change too", () => {
            const selected_tracker = [{ id: 1, label: "timetracking_tracker", disabled: true }];
            mutations.setSelectedTrackers(state, selected_tracker);
            mutations.removeSelectedTracker(state, selected_tracker);
            expect(state.selected_trackers).toEqual([]);
        });

        it("When we set report id, state must change too", () => {
            mutations.setReportId(state, 12);
            expect(state.report_id).toBe(12);
        });

        describe("When trackers are added, state must change too", () => {
            beforeEach(() => {
                let trackers = [
                    { id: 1, label: "timetracking_tracker" },
                    { id: 2, label: "support_tracker" },
                    { id: 3, label: "task_tracker" },
                ];

                mutations.setTrackers(state, trackers);
            });

            it("When we add already existing selected trackers, nothing should change", () => {
                const selected_tracker = [{ id: 1, label: "timetracking_tracker", disabled: true }];
                const tracker_id = 1;

                mutations.addSelectedTrackers(state, tracker_id);
                expect(state.selected_trackers).toEqual(selected_tracker);
            });
        });

        describe("When trackers are set, state must change too", () => {
            beforeEach(() => {
                let trackers = [];
                mutations.setSelectedTrackers(state, trackers);
            });

            it("When no selected_trackers, no tracker are disabled", () => {
                let trackers = [
                    { id: 1, label: "timetracking_tracker" },
                    { id: 2, label: "support_tracker" },
                    { id: 3, label: "task_tracker" },
                ];

                const tracker_temoin = [
                    { id: 1, label: "timetracking_tracker", disabled: false },
                    { id: 2, label: "support_tracker", disabled: false },
                    { id: 3, label: "task_tracker", disabled: false },
                ];

                mutations.setTrackers(state, trackers);
                expect(state.trackers).toEqual(tracker_temoin);
            });

            it("When selected_trackers, tracker identic is disabled", () => {
                mutations.setSelectedTrackers(state, [{ id: 1, label: "timetracking_tracker" }]);
                let trackers = [
                    { id: 1, label: "timetracking_tracker" },
                    { id: 2, label: "support_tracker" },
                    { id: 3, label: "task_tracker" },
                ];

                const tracker_temoin = [
                    { id: 1, label: "timetracking_tracker", disabled: true },
                    { id: 2, label: "support_tracker", disabled: false },
                    { id: 3, label: "task_tracker", disabled: false },
                ];

                mutations.setTrackers(state, trackers);
                expect(state.trackers).toEqual(tracker_temoin);
            });
        });
    });
});

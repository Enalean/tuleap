/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
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

import initial_state from "../../store/state.js";
import * as getters from "../../store/getters.js";
import mutations from "../../store/mutations.js";

describe("Getters Timetracking Overview", () => {
    let state;
    beforeEach(() => {
        state = { ...initial_state };
    });

    describe("Call has error", () => {
        it("Given a widget with state initialisation. When there is an error message, Then has_error should be true", () => {
            const error_message = "this is an error";

            mutations.setErrorMessage(state, error_message);
            expect(getters.has_error(state)).toBe(true);
        });

        it("Given a widget with state initialisation, Then we reset error has_error should be false", () => {
            mutations.resetMessages(state);
            expect(getters.has_error(state)).toBe(false);
        });
    });

    describe("Call sums", () => {
        it("Given a widget with state initialisation, Then set trackers, getters should give total times of all trackers", () => {
            let trackers = [
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
                {
                    id: "18",
                    label: "tracker 2",
                    project: {},
                    uri: "",
                    time_per_user: [
                        {
                            user_name: "user",
                            user_id: 102,
                            minutes: 20,
                        },
                    ],
                },
            ];
            mutations.setTrackersTimes(state, trackers);
            expect(getters.get_formatted_total_sum(state)).toBe("01:20");
        });

        it("Given a widget with state initialisation with selected user, Then set trackers, getters should give total times of all trackers' user", () => {
            state.selected_user = 102;
            let trackers = [
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
                {
                    id: "18",
                    label: "tracker 2",
                    project: {},
                    uri: "",
                    time_per_user: [
                        {
                            user_name: "user_2",
                            user_id: 103,
                            minutes: 60,
                        },
                    ],
                },
            ];
            mutations.setTrackersTimes(state, trackers);
            expect(getters.get_formatted_total_sum(state)).toBe("01:00");
        });

        it("Given a widget with state initialisation, Then get_formatted_time should format total time", () => {
            let trackers = {
                id: "16",
                label: "tracker",
                project: {},
                uri: "",
                time_per_user: [
                    {
                        user_name: "user",
                        user_id: 102,
                        minutes: 120,
                    },
                ],
            };
            mutations.setTrackersTimes(state, [trackers]);
            expect(getters.get_formatted_time(state)(trackers)).toBe("02:00");
        });

        it("Given a widget with state initialisation, Then is_tracker_total_som_equals_zero should be true if no time", () => {
            let trackers = {
                id: "16",
                label: "tracker",
                project: {},
                uri: "",
                time_per_user: [
                    {
                        user_name: "user",
                        user_id: 102,
                        minutes: 0,
                    },
                ],
            };
            mutations.setTrackersTimes(state, [trackers]);
            expect(
                getters.is_tracker_total_sum_equals_zero(state)(trackers.time_per_user),
            ).toBeTruthy();
        });

        it("Given a widget with state initialisation, Then is_tracker_total_som_equals_zero should be false if their is times", () => {
            let trackers = {
                id: "16",
                label: "tracker",
                project: {},
                uri: "",
                time_per_user: [
                    {
                        user_name: "user",
                        user_id: 102,
                        minutes: 120,
                    },
                ],
            };
            mutations.setTrackersTimes(state, [trackers]);
            expect(
                getters.is_tracker_total_sum_equals_zero(state)(trackers.time_per_user),
            ).toBeFalsy();
        });

        it("Given a widget with state initialisation, Then is_sum_of_times_equals_zero should return false", () => {
            let trackers = [
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
                {
                    id: "18",
                    label: "tracker 2",
                    project: {},
                    uri: "",
                    time_per_user: [
                        {
                            user_name: "user",
                            user_id: 102,
                            minutes: 20,
                        },
                    ],
                },
            ];
            mutations.setTrackersTimes(state, trackers);
            expect(getters.is_sum_of_times_equals_zero(state)).toBe(false);
        });

        it("Given trackers without times, Then is_sum_of_times_equals_zero should return true", () => {
            let trackers = [
                {
                    id: "16",
                    label: "tracker",
                    project: {},
                    uri: "",
                    time_per_user: [
                        {
                            user_name: "user",
                            user_id: 102,
                            minutes: 0,
                        },
                    ],
                },
                {
                    id: "18",
                    label: "tracker 2",
                    project: {},
                    uri: "",
                    time_per_user: [
                        {
                            user_name: "user",
                            user_id: 102,
                            minutes: 0,
                        },
                    ],
                },
            ];
            mutations.setTrackersTimes(state, trackers);
            expect(getters.is_sum_of_times_equals_zero(state)).toBe(true);
        });
    });
});

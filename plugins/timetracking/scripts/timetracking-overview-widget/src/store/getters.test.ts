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

import { describe, beforeEach, afterEach, it, expect } from "@jest/globals";
import { setActivePinia, createPinia } from "pinia";
import type { ProjectReference } from "@tuleap/core-rest-api-types";
import type { TrackerWithTimes } from "@tuleap/plugin-timetracking-rest-api-types";
import { useOverviewWidgetTestStore } from "../../tests/helpers/pinia-test-store";
import type { OverviewWidgetStoreInstance } from "../../tests/helpers/pinia-test-store";

describe("Getters Timetracking Overview", (): void => {
    let store: OverviewWidgetStoreInstance;

    beforeEach((): void => {
        setActivePinia(createPinia());
        store = useOverviewWidgetTestStore();
    });

    afterEach((): void => {
        store.$reset();
    });

    describe("Call has error", () => {
        it("Given a widget with state initialisation. When there is an error message, Then has_error should be true", (): void => {
            const error_message = "this is an error";

            store.setErrorMessage(error_message);
            expect(store.has_error).toBe(true);
        });

        it("Given a widget with state initialisation, Then we reset error has_error should be false", (): void => {
            store.resetMessages();
            expect(store.has_error).toBe(false);
        });
    });

    describe("Call sums", (): void => {
        it("Given a widget with state initialisation, Then set trackers, getters should give total times of all trackers", (): void => {
            const trackers: TrackerWithTimes[] = [
                {
                    id: 16,
                    label: "tracker",
                    project: {} as ProjectReference,
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
                    id: 18,
                    label: "tracker 2",
                    project: {} as ProjectReference,
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
            store.setTrackersTimes(trackers);
            expect(store.get_formatted_total_sum).toBe("01:20");
        });

        it("Given a widget with state initialisation with selected user, Then set trackers, getters should give total times of all trackers' user", (): void => {
            store.selected_user_id = 102;
            const trackers: TrackerWithTimes[] = [
                {
                    id: 16,
                    label: "tracker",
                    project: {} as ProjectReference,
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
                    id: 18,
                    label: "tracker 2",
                    project: {} as ProjectReference,
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
            store.setTrackersTimes(trackers);
            expect(store.get_formatted_total_sum).toBe("01:00");
        });

        it("Given a widget with state initialisation, Then get_formatted_time should format total time", (): void => {
            const tracker: TrackerWithTimes = {
                id: 16,
                label: "tracker",
                project: {} as ProjectReference,
                uri: "",
                time_per_user: [
                    {
                        user_name: "user",
                        user_id: 102,
                        minutes: 120,
                    },
                ],
            };
            store.setTrackersTimes([tracker]);
            expect(store.get_formatted_time(tracker)).toBe("02:00");
        });

        it("Given a widget with state initialisation, Then is_tracker_total_som_equals_zero should be true if no time", (): void => {
            const tracker: TrackerWithTimes = {
                id: 16,
                label: "tracker",
                project: {} as ProjectReference,
                uri: "",
                time_per_user: [
                    {
                        user_name: "user",
                        user_id: 102,
                        minutes: 0,
                    },
                ],
            };
            store.setTrackersTimes([tracker]);
            expect(store.is_tracker_total_sum_equals_zero(tracker.time_per_user)).toBeTruthy();
        });

        it("Given a widget with state initialisation, Then is_tracker_total_som_equals_zero should be false if their is times", (): void => {
            const tracker: TrackerWithTimes = {
                id: 16,
                label: "tracker",
                project: {} as ProjectReference,
                uri: "",
                time_per_user: [
                    {
                        user_name: "user",
                        user_id: 102,
                        minutes: 120,
                    },
                ],
            };
            store.setTrackersTimes([tracker]);
            expect(store.is_tracker_total_sum_equals_zero(tracker.time_per_user)).toBeFalsy();
        });

        it("Given a widget with state initialisation, Then is_sum_of_times_equals_zero should return false", (): void => {
            const trackers: TrackerWithTimes[] = [
                {
                    id: 16,
                    label: "tracker",
                    project: {} as ProjectReference,
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
                    id: 18,
                    label: "tracker 2",
                    project: {} as ProjectReference,
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
            store.setTrackersTimes(trackers);
            expect(store.is_sum_of_times_equals_zero).toBe(false);
        });

        it("Given trackers without times, Then is_sum_of_times_equals_zero should return true", (): void => {
            const trackers: TrackerWithTimes[] = [
                {
                    id: 16,
                    label: "tracker",
                    project: {} as ProjectReference,
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
                    id: 18,
                    label: "tracker 2",
                    project: {} as ProjectReference,
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
            store.setTrackersTimes(trackers);
            expect(store.is_sum_of_times_equals_zero).toBe(true);
        });
    });
});

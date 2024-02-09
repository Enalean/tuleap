/*
 * Copyright Enalean (c) 2018 - present. All rights reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import { createPinia, setActivePinia } from "pinia";
import {
    ERROR_OCCURRED,
    REST_FEEDBACK_ADD,
    REST_FEEDBACK_DELETE,
    REST_FEEDBACK_EDIT,
    SUCCESS_TYPE,
} from "@tuleap/plugin-timetracking-constants";
import { usePersonalTimetrackingWidgetStore } from "./root";
import type { PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";

describe("Store mutations", () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });
    describe("Mutations", () => {
        describe("Given a widget with state initialisation", () => {
            it("Then we change reading mode, state must change too", () => {
                const store = usePersonalTimetrackingWidgetStore();
                store.toggleReadingMode();
                expect(store.reading_mode).toBe(false);
            });

            it("Then we put new dates, state must change too", () => {
                const store = usePersonalTimetrackingWidgetStore();
                store.toggleReadingMode();
                store.setParametersForNewQuery("2018-01-01", "2018-02-02");
                expect(store.start_date).toBe("2018-01-01");
                expect(store.end_date).toBe("2018-02-02");
                expect(store.reading_mode).toBe(true);
            });

            it("Then we change rest_error, state must change too", () => {
                const store = usePersonalTimetrackingWidgetStore();
                store.setErrorMessage("oui");
                expect(store.error_message).toBe("oui");
            });

            it("Then we change isLoading, state must change too", () => {
                const store = usePersonalTimetrackingWidgetStore();
                store.setIsLoading(true);
                expect(store.is_loading).toBe(true);
            });

            it("Then we call setAddMode, states must change", () => {
                const store = usePersonalTimetrackingWidgetStore();
                store.setAddMode(true);
                expect(store.is_add_mode).toBe(true);
                expect(store.rest_feedback.message).toBe("");
                expect(store.rest_feedback.type).toBe("");
            });

            it("When states updated with error message, Then we call setAddMode, states must change", () => {
                const store = usePersonalTimetrackingWidgetStore();
                store.rest_feedback.message = REST_FEEDBACK_ADD;
                store.rest_feedback.type = SUCCESS_TYPE;
                store.setAddMode(true);

                expect(store.is_add_mode).toBe(true);
                expect(store.rest_feedback.message).toBe("");
                expect(store.rest_feedback.type).toBe("");
            });

            it("When states updated with error message, Then we call setAddMode without being in add mode, states must change", () => {
                const store = usePersonalTimetrackingWidgetStore();
                store.is_add_mode = true;
                store.rest_feedback.message = ERROR_OCCURRED;
                store.rest_feedback.type = "danger";
                store.setAddMode(false);

                expect(store.is_add_mode).toBe(false);
                expect(store.rest_feedback.message).toBe("");
                expect(store.rest_feedback.type).toBe("");
            });

            it("When states updated with error message, Then we call replaceCurrentTime, states must change", () => {
                const store = usePersonalTimetrackingWidgetStore();
                const times = [
                    {
                        artifact: {},
                        project: {},
                        id: 1,
                        minutes: 20,
                        date: "2023-01-01",
                    },
                    {
                        artifact: {},
                        project: {},
                        id: 2,
                        minutes: 20,
                        date: "2023-01-02",
                    },
                    {
                        artifact: {},
                        project: {},
                        id: 3,
                        minutes: 20,
                        date: "2023-01-04",
                    },
                ] as PersonalTime[];
                store.current_times = times;
                const updated_time = {
                    artifact: {},
                    project: {},
                    id: 1,
                    minutes: 40,
                    date: "2023-01-03",
                } as PersonalTime;
                store.replaceInCurrentTimes(updated_time, REST_FEEDBACK_EDIT);
                expect(store.current_times).toStrictEqual([times[0], updated_time, times[2]]);
                expect(store.rest_feedback.message).toBe(REST_FEEDBACK_EDIT);
                expect(store.rest_feedback.type).toBe(SUCCESS_TYPE);
            });

            it("When we remove the last entry time, then a fake line is added to keep artifact time", () => {
                const store = usePersonalTimetrackingWidgetStore();
                store.current_times = [
                    {
                        artifact: {},
                        project: {},
                        id: 1,
                        minutes: 20,
                    },
                ] as PersonalTime[];
                const deleted_time_id = 1;
                store.deleteInCurrentTimes(deleted_time_id, REST_FEEDBACK_DELETE);
                expect(store.current_times).toHaveLength(0);
                expect(store.rest_feedback.message).toBe(REST_FEEDBACK_DELETE);
                expect(store.rest_feedback.type).toBe(SUCCESS_TYPE);
            });

            it("When we remove a time, then it is removed from list", () => {
                const store = usePersonalTimetrackingWidgetStore();
                store.current_times = [
                    {
                        artifact: {},
                        project: {},
                        id: 1,
                        minutes: 20,
                    },
                    {
                        artifact: {},
                        project: {},
                        id: 2,
                        minutes: 20,
                    },
                ] as PersonalTime[];
                const deleted_time_id = 1;
                store.deleteInCurrentTimes(deleted_time_id, REST_FEEDBACK_DELETE);
                expect(store.current_times).toStrictEqual([
                    {
                        artifact: {},
                        project: {},
                        id: 2,
                        minutes: 20,
                    },
                ]);
                expect(store.rest_feedback.message).toBe(REST_FEEDBACK_DELETE);
                expect(store.rest_feedback.type).toBe(SUCCESS_TYPE);
            });
        });
        describe("Given a new time", () => {
            it("When I call the pushCurrentTimes() mutation with it, Then it should add it to the times collection and sort it chronologically", () => {
                const store = usePersonalTimetrackingWidgetStore();
                const times = [
                    {
                        artifact: {},
                        project: {},
                        id: 1,
                        minutes: 20,
                        date: "2023-01-01",
                    },
                    {
                        artifact: {},
                        project: {},
                        id: 3,
                        minutes: 20,
                        date: "2023-01-03",
                    },
                ] as PersonalTime[];
                store.current_times = times;
                const updated_time = {
                    artifact: {},
                    project: {},
                    id: 2,
                    minutes: 20,
                    date: "2023-01-02",
                } as PersonalTime;
                store.pushCurrentTimes([updated_time], REST_FEEDBACK_EDIT);
                expect(store.current_times).toStrictEqual([times[1], updated_time, times[0]]);
                expect(store.rest_feedback.message).toBe(REST_FEEDBACK_EDIT);
                expect(store.rest_feedback.type).toBe(SUCCESS_TYPE);
            });
        });

        it("Times should be sorted, recent times are displayed first aka 4/12 is displayed before 1/12", () => {
            const store = usePersonalTimetrackingWidgetStore();
            const times = [
                {
                    artifact: {},
                    project: {},
                    id: 1,
                    minutes: 20,
                    date: "2023-01-01",
                },
                {
                    artifact: {},
                    project: {},
                    id: 3,
                    minutes: 20,
                    date: "2023-01-04",
                },
            ] as PersonalTime[];
            store.sortTimes(times);

            expect(times[0].date).toBe("2023-01-04");
            expect(times[1].date).toBe("2023-01-01");
        });

        it("Times should be sorted, even with a null value", () => {
            const store = usePersonalTimetrackingWidgetStore();
            const times = [
                {
                    artifact: {},
                    project: {},
                    id: 1,
                    minutes: 20,
                    date: "2023-01-01",
                },
                {
                    artifact: {},
                    project: {},
                    id: 3,
                    minutes: 20,
                    date: null,
                },
            ] as PersonalTime[];
            store.sortTimes(times);

            expect(times[0].date).toBe("2023-01-01");
            expect(times[1].date).toBeNull();
        });
    });
});

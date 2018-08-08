/*
 * Copyright Enalean (c) 2018. All rights reserved.
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

import mutations from "./mutations.js";
import initial_state from "./state.js";

describe("Store mutations", () => {
    let state;
    beforeEach(() => {
        state = { ...initial_state };
    });

    describe("setters", () => {
        it("Given a widget with state initialisation, Then we change dates, states must be equal to the new dates", () => {
            mutations.setStartDate(state, "2018-03-03");
            mutations.setEndDate(state, "2018-04-03");
            expect(state.start_date).toEqual("2018-03-03");
            expect(state.end_date).toEqual("2018-04-03");
        });

        it("Given a widget with state initialisation, Then we change reading mode, state must change too", () => {
            mutations.toggleReadingMode(state);
            expect(state.reading_mode).toBe(false);
        });

        it("Given a widget with state initialisation, Then we put new dates, state must change too", () => {
            mutations.toggleReadingMode(state);
            mutations.setParametersForNewQuery(state, ["2018-01-01", "2018-02-02"]);
            expect(state.start_date).toEqual("2018-01-01");
            expect(state.end_date).toEqual("2018-02-02");
            expect(state.reading_mode).toBe(true);
        });

        it("Given a widget with state initialisation, Then we add times, times must change too", () => {
            let times = [
                [
                    {
                        artifact: {},
                        project: {},
                        minutes: 20
                    },
                    {
                        artifact: {},
                        project: {},
                        minutes: 20
                    }
                ],
                [
                    {
                        artifact: {},
                        project: {},
                        minutes: 20
                    }
                ]
            ];

            mutations.setTimes(state, times);
            expect(state.times.length).toBe(2);
        });

        it("Given a widget with state initialisation, Then we change total_time, state must change too", () => {
            mutations.setTotalTimes(state, 5);
            expect(state.total_times).toBe(5);
        });

        it("Given a widget with state initialisation, Then we change pagination_offset, state must change too", () => {
            mutations.setPaginationOffset(state, 5);
            expect(state.pagination_offset).toBe(5);
        });

        it("Given a widget with state initialisation, Then we change pagination_limit, state must change too", () => {
            mutations.setPaginationLimit(state, 5);
            expect(state.pagination_limit).toBe(5);
        });

        it("Given a widget with state initialisation, Then we change rest_error, state must change too", () => {
            mutations.setErrorMessage(state, "oui");
            expect(state.error_message).toEqual("oui");
        });

        it("Given a widget with state initialisation, Then we change isLoading, state must change too", () => {
            mutations.setIsLoading(state, true);
            expect(state.is_loading).toBe(true);
        });
    });
});

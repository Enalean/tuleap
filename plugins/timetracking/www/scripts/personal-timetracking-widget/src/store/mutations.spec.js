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

        it("Given a widget with state initialisation, Then we change queryHasChanged, state must change too", () => {
            mutations.setQueryHasChanged(state, true);
            expect(state.query_has_changed).toBe(true);
        });

        it("Given a widget with state initialisation, Then we put new dates, state must change too", () => {
            mutations.toggleReadingMode(state);
            mutations.setDates(state, ["2018-01-01", "2018-02-02"]);
            expect(state.start_date).toEqual("2018-01-01");
            expect(state.end_date).toEqual("2018-02-02");
            expect(state.reading_mode).toBe(true);
            expect(state.query_has_changed).toBe(true);
        });
    });
});

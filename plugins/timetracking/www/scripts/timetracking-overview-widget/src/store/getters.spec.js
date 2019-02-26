/**
 * Copyright Enalean (c) 2019. All rights reserved.
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

import initial_state from "./state.js";
import * as getters from "./getters.js";
import mutations from "./mutations.js";

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
                    minutes: 60
                },
                {
                    id: "18",
                    label: "tracker 2",
                    project: {},
                    uri: "",
                    minutes: 20
                }
            ];
            mutations.setTrackersTimes(state, trackers);
            expect(getters.get_formatted_total_sum(state)).toBe("01:20");
        });

        it("Given a widget with state initialisation, Then get_formatted_time should format total time", () => {
            let trackers = {
                id: "16",
                label: "tracker",
                project: {},
                uri: "",
                minutes: 120
            };
            expect(getters.get_formatted_time()(trackers)).toBe("02:00");
        });
    });
});

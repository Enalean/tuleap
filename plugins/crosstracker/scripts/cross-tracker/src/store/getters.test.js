/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

describe("Store getters", () => {
    let state;
    beforeEach(() => {
        state = { ...initial_state };
    });

    describe("shouldDisplayExportButton", () => {
        it("Given that user is not widget administrator, then he should be able to export results", () => {
            state.is_user_admin = false;
            state.error_message = null;
            state.invalid_trackers = [];

            const result = getters.should_display_export_button(state);

            expect(result).toEqual(true);
        });

        it("Given user is widget administrator and no trackers of query are invalid, then he should be able to export results", () => {
            state.is_user_admin = true;
            state.invalid_trackers = [];

            const result = getters.should_display_export_button(state);

            expect(result).toEqual(true);
        });

        it("Given user is widget administrator and at least one tracker is invalid, then he should not be able to export results", () => {
            state.is_user_admin = true;
            const invalid_tracker = {
                id: 1,
                label: "My invalid tracker",
            };
            state.invalid_trackers = [invalid_tracker];

            const result = getters.should_display_export_button(state);

            expect(result).toEqual(false);
        });

        it("Given report query has an error, nobody should be able to export result", () => {
            state.error_message = "An error occured";

            const result = getters.should_display_export_button(state);

            expect(result).toEqual(false);
        });
    });
});

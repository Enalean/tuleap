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

import { setSelectedSourceBranch, setSelectedDestinationBranch } from "./mutations.js";
import initial_state from "./state.js";

describe("Store mutations", () => {
    const a_branch = { name: "master" };
    let state;
    beforeEach(() => {
        state = { ...initial_state };
    });

    describe("setSelectedSourceBranch", () => {
        it("sets the selected branch", () => {
            setSelectedSourceBranch(state, a_branch);

            expect(state.selected_source_branch).toBe(a_branch);
        });

        it("resets the error state when user select a new branch", () => {
            state.create_error_message = "An error occured";

            setSelectedSourceBranch(state, a_branch);

            expect(state.create_error_message).toBe("");
        });
    });

    describe("setSelectedDestinationBranch", () => {
        it("sets the selected branch", () => {
            setSelectedDestinationBranch(state, a_branch);

            expect(state.selected_destination_branch).toBe(a_branch);
        });

        it("resets the error state when user select a new branch", () => {
            state.create_error_message = "An error occured";

            setSelectedDestinationBranch(state, a_branch);

            expect(state.create_error_message).toBe("");
        });
    });
});

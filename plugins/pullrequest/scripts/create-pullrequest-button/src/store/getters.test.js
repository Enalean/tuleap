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

import * as getters from "./getters.js";
import initial_state from "./state.js";

describe("Store getters", () => {
    let state;
    beforeEach(() => {
        state = { ...initial_state };
    });

    describe("can_create_pullrequest", () => {
        it("returns false if there isn't any source branch", () => {
            state.source_branches = [];
            state.destination_branches = [{ name: "master" }, { name: "feature/branch" }];

            expect(getters.can_create_pullrequest(state)).toBe(false);
        });

        it("returns false if there isn't any destination branch", () => {
            state.source_branches = [{ name: "master" }, { name: "feature/branch" }];
            state.destination_branches = [];

            expect(getters.can_create_pullrequest(state)).toBe(false);
        });

        it("returns false if there is only one source branch and one destination branch", () => {
            state.source_branches = [{ name: "master" }];
            state.destination_branches = [{ name: "master" }];

            expect(getters.can_create_pullrequest(state)).toBe(false);
        });

        it("returns true if there are at least 2 source branches", () => {
            state.source_branches = [{ name: "master" }, { name: "feature/branch" }];
            state.destination_branches = [{ name: "master" }];

            expect(getters.can_create_pullrequest(state)).toBe(true);
        });

        it("returns true if there are at least 2 destination branches", () => {
            state.source_branches = [{ name: "master" }];
            state.destination_branches = [{ name: "master" }, { name: "feature/branch" }];

            expect(getters.can_create_pullrequest(state)).toBe(true);
        });
    });
});

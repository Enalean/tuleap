/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

import * as getters from "./transition-getters.js";
import { create } from "../../support/factories.js";

describe("Transition getters: ", () => {
    describe("is_transition_from_new_artifact()", () => {
        it("returns false when no current transition", () => {
            const state = { current_transition: null };
            expect(getters.is_transition_from_new_artifact(state)).toBeFalsy();
        });

        it("returns true when new artifact is the origin of current transition", () => {
            const state = { current_transition: { from_id: null } };
            expect(getters.is_transition_from_new_artifact(state)).toBeTruthy();
        });

        it("returns false when current transition is not from new artifact", () => {
            const state = { current_transition: { from_id: 3 } };
            expect(getters.is_transition_from_new_artifact(state)).toBeFalsy();
        });
    });

    describe("post_actions()", () => {
        let state;

        describe("when actions are not loaded", () => {
            beforeEach(() => (state = { post_actions_by_unique_id: null }));

            it("returns null", () => {
                expect(getters.post_actions(state)).toBeNull();
            });
        });

        describe("when actions are loaded", () => {
            const action1 = create("post_action");
            const action2 = create("post_action");

            beforeEach(() => {
                state = {
                    post_actions_by_unique_id: {
                        "post-action-1": action1,
                        "post-action-2": action2
                    }
                };
            });

            it("returns all actions", () => {
                expect(getters.post_actions(state)).toEqual([action1, action2]);
            });
        });
    });
});

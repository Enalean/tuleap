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
 *
 */

import mutations from "./mutations.js";
import initial_state from "./state.js";
import { createATransition } from "../support/factories.js";

describe("Store mutations:", () => {
    let state;

    beforeEach(() => {
        state = { ...initial_state };
    });

    describe("deleteTransition()", () => {
        const { deleteTransition } = mutations;
        let transition_to_delete;
        let another_transition;

        beforeEach(() => {
            transition_to_delete = createATransition();
            another_transition = createATransition();
            state.current_tracker = {
                workflow: {
                    transitions: [transition_to_delete, another_transition]
                }
            };
            deleteTransition(state, transition_to_delete);
        });

        it("removes given transition from current tracker transitions", () => {
            expect(state.current_tracker.workflow.transitions).not.toContain(transition_to_delete);
        });
        it("does not remove other transitions", () => {
            expect(state.current_tracker.workflow.transitions).toContain(another_transition);
        });

        describe("when no current tracker", () => {
            beforeEach(() => {
                state.current_tracker = null;
                deleteTransition(state, transition_to_delete);
            });

            it("does nothing", () => {
                expect(state.current_tracker).toBeNull();
            });
        });
    });
});

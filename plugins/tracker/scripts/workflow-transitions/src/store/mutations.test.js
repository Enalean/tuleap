/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import {
    addTransition,
    deleteTransition,
    createWorkflow,
    saveCurrentTracker,
    markTransitionUpdated,
    hideTransitionUpdated,
} from "./mutations.js";
import initial_state from "./state.js";
import { create } from "../support/factories.js";

describe("Store mutations:", () => {
    let state;

    beforeEach(() => {
        state = { ...initial_state };
    });

    describe("saveCurrentTracker()", () => {
        it("adds given tracker and maps all transitions with an 'updated' property", () => {
            const first_transition = { id: 77, from_id: null, to_id: 55 };
            const second_transition = { id: 972, from_id: 678, to_id: 501 };
            const tracker = create("tracker", {
                workflow: {
                    transitions: [first_transition, second_transition],
                },
            });

            saveCurrentTracker(state, tracker);

            expect(state.current_tracker.workflow.transitions).toEqual([
                {
                    ...first_transition,
                    updated: false,
                },
                {
                    ...second_transition,
                    updated: false,
                },
            ]);
        });
    });

    describe("addTransition", () => {
        const transition_to_add = create("transition");
        const another_transition = create("transition", "presented");

        beforeEach(() => {
            state.current_tracker = create("tracker", {
                workflow: {
                    transitions: [another_transition],
                },
            });
            addTransition(state, transition_to_add);
        });

        it("adds given transition, with an 'updated' property", () => {
            expect(state.current_tracker.workflow.transitions).toEqual([
                another_transition,
                {
                    ...transition_to_add,
                    updated: false,
                },
            ]);
        });

        describe("when no current tracker", () => {
            beforeEach(() => {
                state.current_tracker = null;
                addTransition(state, transition_to_add);
            });

            it("does nothing", () => {
                expect(state.current_tracker).toBeNull();
            });
        });

        describe("when current tracker has no transition", () => {
            beforeEach(() => {
                state.current_tracker = create("tracker", { workflow: {} });
                addTransition(state, transition_to_add);
            });

            it("adds given transition to current tracker", () => {
                expect(state.current_tracker.workflow.transitions).toEqual([
                    {
                        ...transition_to_add,
                        updated: false,
                    },
                ]);
            });
        });
    });

    describe("deleteTransition()", () => {
        let transition_to_delete;
        let another_transition;

        beforeEach(() => {
            transition_to_delete = create("transition");
            another_transition = create("transition");
            state.current_tracker = create("tracker", {
                workflow: {
                    transitions: [transition_to_delete, another_transition],
                },
            });
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

    describe("markTransitionUpdated()", () => {
        it("marks the transition as 'just updated'", () => {
            const transition = create("transition", "presented");
            state.current_tracker = create("tracker", {
                workflow: {
                    transitions: [transition],
                },
            });

            markTransitionUpdated(state, transition);

            expect(state.current_tracker.workflow.transitions[0].updated).toBe(true);
        });

        it("Given a transition that isn't in the tracker, it will do nothing", () => {
            const transition = create("transition", "presented");
            state.current_tracker = create("tracker", {
                workflow: {
                    transitions: [],
                },
            });

            markTransitionUpdated(state, transition);

            expect(transition.updated).toBe(false);
        });
    });

    describe("hideTransitionUpdated()", () => {
        it("sets the 'updated' flag on the transition to false", () => {
            const transition = create("transition", "presented", { updated: true });
            state.current_tracker = create("tracker", {
                workflow: {
                    transitions: [transition],
                },
            });

            hideTransitionUpdated(state, transition);

            expect(state.current_tracker.workflow.transitions[0].updated).toBe(false);
        });

        it("Given a transition that isn't in the tracker, it will do nothing", () => {
            const transition = create("transition", "presented", { updated: true });
            state.current_tracker = create("tracker", {
                workflow: {
                    transitions: [],
                },
            });

            hideTransitionUpdated(state, transition);

            expect(transition.updated).toBe(true);
        });
    });

    describe("createWorkflow()", () => {
        const tracker = create("tracker");

        beforeEach(() => {
            state.current_tracker = create("tracker", {
                workflow: {
                    field_id: 1,
                },
            });
            createWorkflow(state, tracker);
        });

        it("updates the tracker", () => {
            expect(state.current_tracker).toBe(tracker);
        });

        describe("when no current tracker", () => {
            beforeEach(() => {
                state.current_tracker = null;
                createWorkflow(state, tracker);
            });

            it("does nothing", () => {
                expect(state.current_tracker).toBeNull();
            });
        });
    });
});

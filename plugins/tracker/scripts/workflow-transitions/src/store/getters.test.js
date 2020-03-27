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
    current_workflow_field,
    workflow_field_label,
    are_transition_rules_enforced,
    current_tracker_id,
    selectbox_fields,
    all_target_states,
    current_workflow_transitions,
} from "./getters.js";
import initial_state from "./state.js";
import { create } from "../support/factories.js";

describe("Store getters:", () => {
    let state, getters;

    beforeEach(() => {
        state = { ...initial_state };
        getters = {};
    });

    describe("current_workflow_field", () => {
        it("returns the current workflow's selected field", () => {
            const workflow_field = create("field", "workflow_compliant", { field_id: 13 });

            state.current_tracker = {
                fields: [create("field"), workflow_field],
                workflow: {
                    field_id: 13,
                },
            };

            expect(current_workflow_field(state)).toEqual(workflow_field);
        });

        it("without tracker, it returns null", () => {
            state.current_tracker = null;

            expect(current_workflow_field(state)).toBeNull();
        });

        it("when workflow field_id is 0, it will return null", () => {
            state.current_tracker = {
                workflow: {
                    field_id: 0,
                },
            };

            expect(current_workflow_field(state)).toBeNull();
        });
    });

    describe("workflow_field_label", () => {
        it("returns label of current tracker workflow field", () => {
            getters.current_workflow_field = create("field", { label: "subjoint" });

            expect(workflow_field_label(state, getters)).toBe("subjoint");
        });

        it("without a current tracker workflow field, it returns null", () => {
            getters.current_workflow_field = null;

            expect(workflow_field_label(state, getters)).toBeNull();
        });
    });

    describe("are_transition_rules_enforced", () => {
        describe("when tracker workflow is inactive", () => {
            beforeEach(() => {
                state.current_tracker = {
                    workflow: create("workflow", "inactive"),
                };
            });
            it("returns false", () => {
                expect(are_transition_rules_enforced(state)).toBeFalsy();
            });
        });

        describe("when tracker workflow is active", () => {
            beforeEach(() => {
                state.current_tracker = {
                    workflow: create("workflow", "active"),
                };
            });
            it("returns false", () => {
                expect(are_transition_rules_enforced(state)).toBeTruthy();
            });
        });

        describe("without tracker", () => {
            beforeEach(() => (state.current_tracker = null));
            it("returns null", () => {
                expect(are_transition_rules_enforced(state)).toBeNull();
            });
        });
    });

    describe("current_tracker_id", () => {
        beforeEach(() => (state.current_tracker = create("tracker", { id: 1 })));

        it("returns current tracker id", () => {
            expect(current_tracker_id(state)).toBe(1);
        });

        describe("without tracker", () => {
            beforeEach(() => (state.current_tracker = null));
            it("returns null", () => {
                expect(current_tracker_id(state)).toBeNull();
            });
        });
    });

    describe("selectbox fields", () => {
        beforeEach(() => {
            const fields = [
                create("field", "workflow_compliant", { field_id: 64 }),
                create("field", "selectbox_users", { field_id: 20 }),
                create("field", { field_id: 93, type: "column" }),
                create("field", "workflow_compliant", { field_id: 55 }),
            ];
            state.current_tracker = create("tracker", { id: 85, fields });
        });

        it("filters out fields that aren't selectboxes", () => {
            const selectbox_field_ids = selectbox_fields(state).map(({ id }) => id);
            expect(selectbox_field_ids).not.toContain(93);
        });

        it("filters out selectboxes that aren't bound to static values", () => {
            const selectbox_field_ids = selectbox_fields(state).map(({ id }) => id);
            expect(selectbox_field_ids).not.toContain(20);
        });

        it("returns fields sorted by natural order", () => {
            state.current_tracker.fields = [
                create("field", "workflow_compliant", { label: "second" }),
                create("field", "workflow_compliant", { label: "First" }),
                create("field", "workflow_compliant", { label: "Third" }),
            ];
            expect(selectbox_fields(state).map((field) => field.label)).toEqual([
                "First",
                "second",
                "Third",
            ]);
        });

        describe("without tracker", () => {
            beforeEach(() => (state.current_tracker = null));
            it("returns an empty array", () => {
                expect(selectbox_fields(state)).toEqual([]);
            });
        });
    });

    describe("all_target_states", () => {
        let value, hidden_value;

        beforeEach(() => {
            state.current_tracker = create("tracker");
            value = create("field_value");
            hidden_value = create("field_value", "hidden");
            getters.current_workflow_field = create("field", {
                values: [value, hidden_value],
            });
        });

        it("returns the current workflow field's values", () => {
            expect(all_target_states(state, getters)).toEqual([value]);
        });

        it("filters out values that are hidden", () => {
            expect(all_target_states(state, getters)).not.toContain(hidden_value);
        });

        it("without a current workflow field, it returns an empty array", () => {
            getters.current_workflow_field = null;

            expect(all_target_states(state, getters)).toEqual([]);
        });

        it("without field values, it returns an empty array", () => {
            getters.current_workflow_field = create("field", { values: null });

            expect(all_target_states(state, getters)).toEqual([]);
        });
    });

    describe("current_workflow_transitions", () => {
        it("returns the current tracker workflow's transitions", () => {
            const first_transition = {
                id: 70,
                from_id: 295,
                to_id: 683,
            };
            const second_transition = {
                id: 709,
                from_id: 318,
                to_id: 99,
            };
            state.current_tracker = create("tracker", {
                workflow: {
                    transitions: [first_transition, second_transition],
                },
            });

            expect(current_workflow_transitions(state)).toEqual([
                first_transition,
                second_transition,
            ]);
        });

        it("without tracker, it returns an empty array", () => {
            state.current_tracker = null;

            expect(current_workflow_transitions(state)).toEqual([]);
        });
    });
});

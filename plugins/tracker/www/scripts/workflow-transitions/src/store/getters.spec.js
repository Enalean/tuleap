/*
 * Copyright (c) Enalean, 2018 - 2019. All Rights Reserved.
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
    workflow_field_label,
    are_transition_rules_enforced,
    current_tracker_id,
    selectbox_fields
} from "./getters.js";
import initial_state from "./state.js";
import { create } from "../support/factories.js";

describe("Store getters:", () => {
    let state;

    beforeEach(() => (state = { ...initial_state }));

    describe("workflow_field_label()", () => {
        beforeEach(() => {
            state.current_tracker = {
                fields: [
                    create("field", { field_id: 1 }),
                    create("field", { field_id: 2, label: "Workflow field label" })
                ],
                workflow: {
                    field_id: 2
                }
            };
        });

        it("returns label of current tracker workflow field", () => {
            expect(workflow_field_label(state)).toBe("Workflow field label");
        });

        describe("without tracker", () => {
            beforeEach(() => (state.current_tracker = null));

            it("returns null", () => {
                expect(workflow_field_label(state)).toBeNull();
            });
        });

        it("when workflow field_id is 0, it will return null", () => {
            state.current_tracker = {
                workflow: {
                    field_id: 0
                }
            };

            expect(workflow_field_label(state)).toBeNull();
        });
    });

    describe("are_transition_rules_enforced", () => {
        describe("when tracker workflow is inactive", () => {
            beforeEach(() => {
                state.current_tracker = {
                    workflow: create("workflow", "inactive")
                };
            });
            it("returns false", () => {
                expect(are_transition_rules_enforced(state)).toBeFalsy();
            });
        });

        describe("when tracker workflow is active", () => {
            beforeEach(() => {
                state.current_tracker = {
                    workflow: create("workflow", "active")
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
                create("field", "workflow_compliant", { field_id: 55 })
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
                create("field", "workflow_compliant", { label: "Third" })
            ];
            expect(selectbox_fields(state).map(field => field.label)).toEqual([
                "First",
                "second",
                "Third"
            ]);
        });

        describe("without tracker", () => {
            beforeEach(() => (state.current_tracker = null));
            it("returns an empty array", () => {
                expect(selectbox_fields(state)).toEqual([]);
            });
        });
    });
});

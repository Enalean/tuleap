/*
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import { ref } from "vue";
import { CONTAINER_FIELDSET, STRING_FIELD } from "@tuleap/plugin-tracker-constants";
import {
    buildDraggableElement,
    buildDropzoneElement,
} from "../tests/builders/build-drag-and-drop-elements";
import type { Field, Fieldset } from "../type";
import { ROOT_CONTAINER_ID } from "../type";
import type { DropRulesEnforcer } from "./DropRulesEnforcer";
import { getDropRulesEnforcer } from "./DropRulesEnforcer";

describe("DropRulesEnforcer", () => {
    let drop_rules_enforcer: DropRulesEnforcer,
        string_field: Field,
        fieldset_1: Fieldset,
        fieldset_2: Fieldset;

    beforeEach(() => {
        string_field = { field: { field_id: 3 }, type: STRING_FIELD } as unknown as Field;
        fieldset_1 = {
            field: { field_id: 1, type: CONTAINER_FIELDSET, label: "Fieldset 1" },
            children: [string_field],
        } as unknown as Fieldset;
        fieldset_2 = {
            field: { field_id: 2, type: CONTAINER_FIELDSET, label: "Fieldset 2" },
            children: [],
        } as unknown as Fieldset;

        drop_rules_enforcer = getDropRulesEnforcer(
            ref({
                children: [fieldset_1, fieldset_2],
            }),
        );
    });

    describe("Given that the target dropzone is the root of the tracker", () => {
        it("When the dragged element is not a fieldset, Then it should return false", () => {
            const is_drop_possible = drop_rules_enforcer.isDropPossible({
                dragged_element: buildDraggableElement(string_field.field.field_id),
                source_dropzone: buildDropzoneElement(fieldset_1.field.field_id),
                target_dropzone: buildDropzoneElement(ROOT_CONTAINER_ID),
            });

            expect(is_drop_possible).toBe(false);
        });

        it("When the dragged element is a fieldset, Then it should return true", () => {
            const is_drop_possible = drop_rules_enforcer.isDropPossible({
                dragged_element: buildDraggableElement(fieldset_1.field.field_id),
                source_dropzone: buildDropzoneElement(ROOT_CONTAINER_ID),
                target_dropzone: buildDropzoneElement(ROOT_CONTAINER_ID),
            });

            expect(is_drop_possible).toBe(true);
        });
    });

    describe("Given that the target dropzone is NOT the root of the tracker", () => {
        it("When the dragged element is not a fieldset, Then it should return true", () => {
            const is_drop_possible = drop_rules_enforcer.isDropPossible({
                dragged_element: buildDraggableElement(string_field.field.field_id),
                source_dropzone: buildDropzoneElement(fieldset_1.field.field_id),
                target_dropzone: buildDropzoneElement(fieldset_2.field.field_id),
            });

            expect(is_drop_possible).toBe(true);
        });

        it("When the dragged element is a fieldset, Then it should return false", () => {
            const is_drop_possible = drop_rules_enforcer.isDropPossible({
                dragged_element: buildDraggableElement(fieldset_1.field.field_id),
                source_dropzone: buildDropzoneElement(ROOT_CONTAINER_ID),
                target_dropzone: buildDropzoneElement(fieldset_2.field.field_id),
            });

            expect(is_drop_possible).toBe(false);
        });
    });
});

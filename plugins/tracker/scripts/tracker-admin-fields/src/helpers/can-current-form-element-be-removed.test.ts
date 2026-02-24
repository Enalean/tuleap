/*
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import { canCurrentFormElementBeRemoved } from "./can-current-form-element-be-removed";
import {
    CONTAINER_FIELDSET,
    CONTAINER_COLUMN,
    STRING_FIELD,
} from "@tuleap/plugin-tracker-constants";
import type { Fieldset, Column, Field } from "../type";
import { buildField } from "../tests/builders/SimpleStructuralFieldTestBuilder";

describe("canCurrentFormElementBeRemoved", () => {
    /**
     * children
     * ├── field_in_root (ID: 0, STRING_FIELD)
     * ├── fieldset_with_children (ID: 1, CONTAINER_FIELDSET)
     * │   ├── field_1 (ID: 10, STRING_FIELD)
     * │   ├── field_2 (ID: 11, STRING_FIELD)
     * │   └── empty_fieldset_in_fieldset_1 (ID: 12, CONTAINER_FIELDSET)
     * ├── empty_fieldset (ID: 2, CONTAINER_FIELDSET)
     * └── fieldset_with_columns (ID: 4, CONTAINER_FIELDSET)
     *     ├── column_with_children (ID: 40, CONTAINER_COLUMN)
     *     │   ├── field_column_1 (ID: 401, STRING_FIELD)
     *     │   └── field_column_2 (ID: 402, STRING_FIELD)
     *     └── empty_column (ID: 41, CONTAINER_COLUMN)
     */
    const field_in_root: Field = {
        field: buildField(0, STRING_FIELD),
    };

    const field_1: Field = {
        field: buildField(10, STRING_FIELD),
    };

    const field_2: Field = {
        field: buildField(11, STRING_FIELD),
    };

    const empty_fieldset_in_fieldset_1: Fieldset = {
        field: buildField(12, CONTAINER_FIELDSET),
        children: [],
    };

    const fieldset_with_children: Fieldset = {
        field: buildField(1, CONTAINER_FIELDSET),
        children: [field_1, field_2, empty_fieldset_in_fieldset_1],
    };

    const empty_fieldset: Fieldset = {
        field: buildField(2, CONTAINER_FIELDSET),
        children: [],
    };

    const field_column_1: Field = {
        field: buildField(301, STRING_FIELD),
    };
    const field_column_2: Field = {
        field: buildField(302, STRING_FIELD),
    };
    const column_with_children: Column = {
        field: buildField(30, CONTAINER_COLUMN),
        children: [field_column_1, field_column_2],
    };

    const empty_column: Column = {
        field: buildField(41, CONTAINER_COLUMN),
        children: [],
    };

    const fieldset_with_columns: Fieldset = {
        field: buildField(3, CONTAINER_FIELDSET),
        children: [column_with_children, empty_column],
    };

    const children = [field_in_root, fieldset_with_children, empty_fieldset, fieldset_with_columns];

    const non_existing_fieldset: Fieldset = {
        field: buildField(313, CONTAINER_FIELDSET),
        children: [column_with_children, empty_column],
    };

    const non_existing_field: Fieldset = {
        field: buildField(45754, CONTAINER_FIELDSET),
        children: [column_with_children, empty_column],
    };

    it.each([[empty_fieldset_in_fieldset_1], [empty_fieldset], [empty_column]])(
        `return true when an empty fieldset is found`,
        (fieldset: Fieldset | Column) => {
            const result = canCurrentFormElementBeRemoved(children, fieldset.field.field_id);
            expect(result).toBe(true);
        },
    );
    it.each([[fieldset_with_children], [fieldset_with_columns]])(
        `return false when the found fieldset is not empty`,
        (fieldset: Fieldset | Column) => {
            const result = canCurrentFormElementBeRemoved(children, fieldset.field.field_id);
            expect(result).toBe(false);
        },
    );
    it.each([[field_1], [field_in_root]])(
        `return true if the given field is found`,
        (field: Field) => {
            const result = canCurrentFormElementBeRemoved(children, field.field.field_id);
            expect(result).toBe(true);
        },
    );
    it.each([[non_existing_field], [non_existing_fieldset]])(
        `return false when the found fieldset is not empty`,
        (fieldset: Field | Fieldset | Column) => {
            const result = canCurrentFormElementBeRemoved(children, fieldset.field.field_id);
            expect(result).toBe(false);
        },
    );
});

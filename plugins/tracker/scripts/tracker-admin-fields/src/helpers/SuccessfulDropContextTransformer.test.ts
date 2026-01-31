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
import { v4 as uuidv4 } from "uuid";
import { type SuccessfulDropCallbackParameter } from "@tuleap/drag-and-drop";
import {
    CONTAINER_COLUMN,
    CONTAINER_FIELDSET,
    INT_FIELD,
    STRING_FIELD,
} from "@tuleap/plugin-tracker-constants";
import {
    buildDraggableElement,
    buildDropzoneElement,
} from "../tests/builders/build-drag-and-drop-elements";
import type { Column, ColumnWrapper, ElementWithChildren, Field, Fieldset } from "../type";
import { ROOT_CONTAINER_ID } from "../type";
import {
    getSuccessfulDropContextTransformer,
    type TransformedDropContext,
} from "./SuccessfulDropContextTransformer";

describe("SuccessfulDropContextTransformer", () => {
    let tracker_root: ElementWithChildren,
        string_field: Field,
        int_field: Field,
        fieldset_1: Fieldset,
        fieldset_2: Fieldset,
        column_wrapper: ColumnWrapper,
        column: Column;

    beforeEach(() => {
        string_field = { field: { field_id: 3 }, type: STRING_FIELD } as unknown as Field;
        int_field = { field: { field_id: 4 }, type: INT_FIELD } as unknown as Field;
        column = {
            field: { field_id: 5 },
            type: CONTAINER_COLUMN,
            children: [],
        } as unknown as Column;
        column_wrapper = { identifier: uuidv4(), columns: [column] } as ColumnWrapper;
        fieldset_1 = {
            field: { field_id: 1, type: CONTAINER_FIELDSET, label: "Fieldset 1" },
            children: [string_field],
        } as unknown as Fieldset;
        fieldset_2 = {
            field: { field_id: 2, type: CONTAINER_FIELDSET, label: "Fieldset 2" },
            children: [column_wrapper, int_field],
        } as unknown as Fieldset;
        tracker_root = { children: [fieldset_1, fieldset_2] };
    });

    const getTransformedContext = (
        context: SuccessfulDropCallbackParameter,
    ): TransformedDropContext => {
        const result = getSuccessfulDropContextTransformer(
            ref(tracker_root),
        ).transformSuccessfulDropContext(context);
        if (!result.isOk()) {
            throw new Error(`Expected an ok, got: ${result.error}`);
        }

        return result.value;
    };

    it("When there is a next sibling, Then it should return a TransformedDropContext containing a next sibling", () => {
        const transformed_context = getTransformedContext({
            dropped_element: buildDraggableElement(string_field.field.field_id),
            source_dropzone: buildDropzoneElement(fieldset_1.field.field_id),
            next_sibling: buildDraggableElement(int_field.field.field_id),
            target_dropzone: buildDropzoneElement(fieldset_2.field.field_id),
        });

        expect(transformed_context).toStrictEqual({
            moved_element: string_field,
            source_parent: fieldset_1,
            next_sibling: int_field,
            destination_parent: fieldset_2,
        });
    });

    it("When the next sibling is a ColumnWrapper, Then it should return a TransformedDropContext containing a next sibling", () => {
        const transformed_context = getTransformedContext({
            dropped_element: buildDraggableElement(string_field.field.field_id),
            source_dropzone: buildDropzoneElement(fieldset_1.field.field_id),
            next_sibling: buildDraggableElement(int_field.field.field_id),
            target_dropzone: buildDropzoneElement(fieldset_2.field.field_id),
        });

        expect(transformed_context).toStrictEqual({
            moved_element: string_field,
            source_parent: fieldset_1,
            next_sibling: int_field,
            destination_parent: fieldset_2,
        });
    });

    it("When there is not a next sibling, Then it should return a TransformedDropContext without a next sibling", () => {
        const transformed_context = getTransformedContext({
            dropped_element: buildDraggableElement(string_field.field.field_id),
            source_dropzone: buildDropzoneElement(fieldset_1.field.field_id),
            next_sibling: buildDraggableElement(column_wrapper.identifier),
            target_dropzone: buildDropzoneElement(fieldset_2.field.field_id),
        });

        expect(transformed_context).toStrictEqual({
            moved_element: string_field,
            source_parent: fieldset_1,
            next_sibling: column_wrapper,
            destination_parent: fieldset_2,
        });
    });

    it("When the source and/or target dropzone is the tracker root, Then it should return a TransformedDropContext", () => {
        const transformed_context = getTransformedContext({
            dropped_element: buildDraggableElement(fieldset_2.field.field_id),
            source_dropzone: buildDropzoneElement(ROOT_CONTAINER_ID),
            next_sibling: buildDraggableElement(fieldset_1.field.field_id),
            target_dropzone: buildDropzoneElement(ROOT_CONTAINER_ID),
        });

        expect(transformed_context).toStrictEqual({
            moved_element: fieldset_2,
            source_parent: tracker_root,
            next_sibling: fieldset_1,
            destination_parent: tracker_root,
        });
    });
});

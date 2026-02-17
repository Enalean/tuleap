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

import { describe, it, expect } from "vitest";
import { v4 as uuidv4 } from "uuid";
import { CONTAINER_COLUMN, FLOAT_FIELD } from "@tuleap/plugin-tracker-constants";
import { buildMoveFieldsAPIRequestParams } from "./build-move-fields-api-request-params";
import type { Column, ColumnWrapper, Field } from "../type";

describe("build-move-fields-api-request-params", () => {
    const moved_element = { field: { field_id: 1, type: FLOAT_FIELD } } as Field;
    const next_sibling_in_parent = { field: { field_id: 3, type: FLOAT_FIELD } } as Field;
    const next_sibling_column = { field: { field_id: 4, type: CONTAINER_COLUMN } } as Column;
    const column_wrapper = {
        identifier: uuidv4(),
        columns: [next_sibling_column],
    } as ColumnWrapper;
    const destination_parent = {
        field: { field_id: 2, type: CONTAINER_COLUMN },
        children: [next_sibling_in_parent, column_wrapper],
    } as Column;

    it("When there is no next sibling, then its id should be null", () => {
        expect(
            buildMoveFieldsAPIRequestParams(moved_element, destination_parent, null),
        ).toStrictEqual({
            field_id: moved_element.field.field_id,
            parent_id: destination_parent.field.field_id,
            next_sibling_id: null,
        });
    });

    it("When the next sibling is a Field, then its id should be set in the params", () => {
        expect(
            buildMoveFieldsAPIRequestParams(
                moved_element,
                destination_parent,
                next_sibling_in_parent,
            ),
        ).toStrictEqual({
            field_id: moved_element.field.field_id,
            parent_id: destination_parent.field.field_id,
            next_sibling_id: next_sibling_in_parent.field.field_id,
        });
    });

    it(`
        When the next sibling is a ColumnWrapper,
        Then the next sibling id should be the one of the first column inside the ColumnWrapper
        Because ColumnWrappers are not actual form elements but technical elements for a display purpose.
    `, () => {
        expect(
            buildMoveFieldsAPIRequestParams(moved_element, destination_parent, column_wrapper),
        ).toStrictEqual({
            field_id: moved_element.field.field_id,
            parent_id: destination_parent.field.field_id,
            next_sibling_id: next_sibling_column.field.field_id,
        });
    });
});

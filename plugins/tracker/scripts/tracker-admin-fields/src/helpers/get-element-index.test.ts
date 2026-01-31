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
import { v4 as uuidv4 } from "uuid";
import { CONTAINER_FIELDSET } from "@tuleap/plugin-tracker-constants";
import type { ColumnWrapper, Field, Fieldset } from "../type";
import { getFieldIndexInParent } from "./get-element-index";

describe("get-element-index", () => {
    let parent: Fieldset, column_wrapper: ColumnWrapper, field_1: Field, field_2: Field;

    const column_wrapper_identifier = uuidv4();

    beforeEach(() => {
        column_wrapper = { identifier: column_wrapper_identifier, columns: [] };
        field_2 = { field: { field_id: 124 } } as Field;
        field_1 = { field: { field_id: 123 } } as Field;
        parent = {
            field: { type: CONTAINER_FIELDSET },
            children: [field_1, field_2, column_wrapper],
        } as unknown as Fieldset;
    });

    it("returns the index of a Field in its parent", () => {
        const result = getFieldIndexInParent(parent, {
            field: { field_id: 124 },
        } as unknown as Field);
        if (!result.isOk()) {
            throw new Error("Expected an ok.");
        }
        expect(result.value).toBe(1);
    });

    it("returns the index of a ColumnWrapper in its parent", () => {
        const result = getFieldIndexInParent(parent, {
            identifier: column_wrapper_identifier,
            columns: [],
        } as unknown as ColumnWrapper);
        if (!result.isOk()) {
            throw new Error("Expected an ok.");
        }
        expect(result.value).toBe(2);
    });

    it("returns a Fault when the index is not found", () => {
        const result = getFieldIndexInParent(parent, {
            field: { field_id: 888 },
        } as unknown as Field);

        expect(result.isErr()).toBe(true);
    });
});

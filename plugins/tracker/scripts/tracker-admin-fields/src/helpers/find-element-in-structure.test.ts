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
import {
    CONTAINER_COLUMN,
    CONTAINER_FIELDSET,
    ARTIFACT_ID_FIELD,
    DATE_FIELD,
    FLOAT_FIELD,
    INT_FIELD,
    STRING_FIELD,
} from "@tuleap/plugin-tracker-constants";
import type { ElementWithChildren, Fieldset } from "../type";
import { findElementInStructure } from "./find-element-in-structure";

describe("find-element-in-structure", () => {
    let tracker_root: ElementWithChildren;

    const column_wrapper_2_identifier = uuidv4();

    beforeEach(() => {
        tracker_root = {
            children: [
                {
                    field: { field_id: 1, type: CONTAINER_FIELDSET },
                    children: [
                        {
                            identifier: uuidv4,
                            columns: [
                                {
                                    field: { field_id: 4, type: CONTAINER_COLUMN },
                                    children: [
                                        { field: { field_id: 6, type: STRING_FIELD } },
                                        { field: { field_id: 7, type: DATE_FIELD } },
                                    ],
                                },
                                {
                                    field: { field_id: 5, type: CONTAINER_COLUMN },
                                    children: [
                                        {
                                            identifier: column_wrapper_2_identifier,
                                            columns: [
                                                {
                                                    field: { field_id: 8, type: CONTAINER_COLUMN },
                                                    children: [
                                                        {
                                                            field: {
                                                                field_id: 9,
                                                                type: ARTIFACT_ID_FIELD,
                                                            },
                                                        },
                                                    ],
                                                },
                                            ],
                                        },
                                    ],
                                },
                            ],
                        },
                    ],
                } as unknown as Fieldset,
                {
                    field: { field_id: 2, type: CONTAINER_FIELDSET },
                    children: [{ field: { field_id: 10, type: FLOAT_FIELD } }],
                } as unknown as Fieldset,
                {
                    field: { field_id: 3, type: CONTAINER_FIELDSET },
                    children: [{ field: { field_id: 11, type: INT_FIELD } }],
                } as unknown as Fieldset,
            ],
        };
    });

    it("Given the id of an existing element and a parent element containing it, then it should return the field", () => {
        expect(findElementInStructure(9, tracker_root.children)).toStrictEqual({
            field: { field_id: 9, type: ARTIFACT_ID_FIELD },
        });
        expect(findElementInStructure(5, tracker_root.children)).toStrictEqual({
            field: { field_id: 5, type: CONTAINER_COLUMN },
            children: [
                {
                    identifier: column_wrapper_2_identifier,
                    columns: [
                        {
                            field: { field_id: 8, type: CONTAINER_COLUMN },
                            children: [{ field: { field_id: 9, type: ARTIFACT_ID_FIELD } }],
                        },
                    ],
                },
            ],
        });
        expect(findElementInStructure(3, tracker_root.children)).toStrictEqual({
            field: { field_id: 3, type: CONTAINER_FIELDSET },
            children: [{ field: { field_id: 11, type: INT_FIELD } }],
        });
        expect(
            findElementInStructure(column_wrapper_2_identifier, tracker_root.children),
        ).toStrictEqual({
            identifier: column_wrapper_2_identifier,
            columns: [
                {
                    field: { field_id: 8, type: CONTAINER_COLUMN },
                    children: [{ field: { field_id: 9, type: ARTIFACT_ID_FIELD } }],
                },
            ],
        });
    });

    it("When no element is found using the provided id, then it should return null", () => {
        expect(findElementInStructure(88888, tracker_root.children)).toBeNull();
    });
});

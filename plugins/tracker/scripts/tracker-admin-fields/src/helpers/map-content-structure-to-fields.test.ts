/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { CONTAINER_COLUMN, CONTAINER_FIELDSET } from "@tuleap/plugin-tracker-constants";
import { mapContentStructureToFields } from "./map-content-structure-to-fields";
import type {
    StructureFields,
    TrackerResponseNoInstance,
} from "@tuleap/plugin-tracker-rest-api-types";

describe("mapContentStructureToFields", () => {
    const fieldset: StructureFields = {
        field_id: 123,
        name: "details",
        label: "Details",
        type: CONTAINER_FIELDSET,
        required: false,
    };
    const column: StructureFields = {
        field_id: 124,
        name: "col0",
        label: "col0",
        type: CONTAINER_COLUMN,
        required: false,
    };

    const fields: TrackerResponseNoInstance["fields"] = [fieldset, column];

    it("should return empty array when no content", () => {
        expect(mapContentStructureToFields(null, fields)).toStrictEqual([]);
    });

    it("should return the corresponding fields based on content information", () => {
        expect(
            mapContentStructureToFields(
                [
                    { id: 124, content: null },
                    { id: 123, content: [{ id: 1231, content: null }] },
                ],
                fields,
            ),
        ).toStrictEqual([
            { field: column, content: null },
            { field: fieldset, content: [{ id: 1231, content: null }] },
        ]);
    });
});

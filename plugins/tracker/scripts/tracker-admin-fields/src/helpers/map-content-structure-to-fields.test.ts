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
import {
    CONTAINER_COLUMN,
    CONTAINER_FIELDSET,
    LAST_UPDATE_DATE_FIELD,
    LAST_UPDATED_BY_FIELD,
    STATIC_RICH_TEXT,
    STRING_FIELD,
    SUBMISSION_DATE_FIELD,
    SUBMITTED_BY_FIELD,
} from "@tuleap/plugin-tracker-constants";
import { mapContentStructureToFields } from "./map-content-structure-to-fields";
import type {
    StructureFields,
    StructureFormat,
    TrackerResponseNoInstance,
} from "@tuleap/plugin-tracker-rest-api-types";

describe("mapContentStructureToFields", () => {
    const summary: StructureFields = {
        field_id: 122,
        name: "summary",
        label: "Summary",
        type: STRING_FIELD,
        required: false,
        specific_properties: {
            size: 42,
            maxchars: 0,
            default_value: "",
        },
    };

    const fieldset: StructureFields = {
        field_id: 123,
        name: "details",
        label: "Details",
        type: CONTAINER_FIELDSET,
        required: false,
    };

    const staticrichtext: StructureFields = {
        field_id: 124,
        name: "static",
        label: "Static",
        type: STATIC_RICH_TEXT,
        required: false,
        default_value: "",
    };

    const column_0: StructureFields = {
        field_id: 125,
        name: "col0",
        label: "col0",
        type: CONTAINER_COLUMN,
        required: false,
    };

    const column_1: StructureFields = {
        field_id: 126,
        name: "col1",
        label: "col1",
        type: CONTAINER_COLUMN,
        required: false,
    };

    const lubby: StructureFields = {
        field_id: 127,
        name: "lubby",
        label: "Last updated by",
        type: LAST_UPDATED_BY_FIELD,
        required: false,
    };

    const lud: StructureFields = {
        field_id: 128,
        name: "lud",
        label: "Last updated on",
        type: LAST_UPDATE_DATE_FIELD,
        required: false,
        is_time_displayed: true,
    };

    const subby: StructureFields = {
        field_id: 129,
        name: "subby",
        label: "Submitted by",
        type: SUBMITTED_BY_FIELD,
        required: false,
    };

    const subon: StructureFields = {
        field_id: 130,
        name: "subon",
        label: "Submitted on",
        type: SUBMISSION_DATE_FIELD,
        required: false,
        is_time_displayed: true,
    };

    const fields: TrackerResponseNoInstance["fields"] = [
        summary,
        fieldset,
        staticrichtext,
        column_0,
        column_1,
        lubby,
        lud,
        subby,
        subon,
    ];

    const structure: StructureFormat["content"] = [
        { id: summary.field_id, content: null },
        {
            id: fieldset.field_id,
            content: [
                { id: staticrichtext.field_id, content: null },
                {
                    id: column_0.field_id,
                    content: [
                        { id: lubby.field_id, content: null },
                        { id: lud.field_id, content: null },
                    ],
                },
                {
                    id: column_1.field_id,
                    content: [
                        { id: subby.field_id, content: null },
                        { id: subon.field_id, content: null },
                    ],
                },
            ],
        },
    ];

    it("should return empty array when no content", () => {
        expect(mapContentStructureToFields(null, fields)).toStrictEqual({ children: [] });
    });

    it("should return the corresponding fields based on content information", () => {
        expect(mapContentStructureToFields(structure, fields)).toStrictEqual({
            children: [
                { field: summary },
                {
                    field: fieldset,
                    children: [
                        { field: staticrichtext },
                        {
                            columns: [
                                {
                                    field: column_0,
                                    children: [{ field: lubby }, { field: lud }],
                                },
                                {
                                    field: column_1,
                                    children: [{ field: subby }, { field: subon }],
                                },
                            ],
                        },
                    ],
                },
            ],
        });
    });
});

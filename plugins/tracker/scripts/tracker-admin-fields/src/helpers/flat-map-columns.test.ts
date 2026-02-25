/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
import { flatMapColumns } from "./flat-map-columns";
import {
    CONTAINER_COLUMN,
    CONTAINER_FIELDSET,
    STRING_FIELD,
} from "@tuleap/plugin-tracker-constants";
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";

describe("flatMapColumns", () => {
    const summary: StructureFields = {
        field_id: 122,
        name: "summary",
        label: "Summary",
        type: STRING_FIELD,
        required: false,
        has_notifications: false,
        label_decorators: [],
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
        has_notifications: false,
        label_decorators: [],
    };

    const column_0: StructureFields = {
        field_id: 125,
        name: "col0",
        label: "col0",
        type: CONTAINER_COLUMN,
        required: false,
        has_notifications: false,
        label_decorators: [],
    };

    const column_1: StructureFields = {
        field_id: 126,
        name: "col1",
        label: "col1",
        type: CONTAINER_COLUMN,
        required: false,
        has_notifications: false,
        label_decorators: [],
    };

    it("should return empty array when container is empty", () => {
        expect(
            flatMapColumns({
                children: [],
            }),
        ).toStrictEqual([]);
    });

    it("should return empty array when container does not contain columns", () => {
        expect(
            flatMapColumns({
                children: [
                    {
                        field: fieldset,
                        children: [],
                    },
                    {
                        field: summary,
                    },
                ],
            }),
        ).toStrictEqual([]);
    });

    it("should return columns that are at the root of the container", () => {
        expect(
            flatMapColumns({
                children: [
                    {
                        field: fieldset,
                        children: [],
                    },
                    {
                        identifier: "whatever",
                        columns: [
                            {
                                field: column_1,
                                children: [],
                            },
                            {
                                field: column_0,
                                children: [],
                            },
                        ],
                    },
                ],
            }),
        ).toStrictEqual([
            { field: column_1, children: [] },
            { field: column_0, children: [] },
        ]);
    });

    it("should return columns that are in a fieldset of the container", () => {
        expect(
            flatMapColumns({
                children: [
                    {
                        field: fieldset,
                        children: [
                            {
                                identifier: "whatever",
                                columns: [
                                    {
                                        field: column_1,
                                        children: [],
                                    },
                                    {
                                        field: column_0,
                                        children: [],
                                    },
                                ],
                            },
                        ],
                    },
                ],
            }),
        ).toStrictEqual([
            { field: column_1, children: [] },
            { field: column_0, children: [] },
        ]);
    });

    it("should return columns that are in a column of the container", () => {
        expect(
            flatMapColumns({
                children: [
                    {
                        identifier: "whatever",
                        columns: [
                            {
                                field: column_1,
                                children: [
                                    {
                                        identifier: "whatever",
                                        columns: [
                                            {
                                                field: column_0,
                                                children: [],
                                            },
                                        ],
                                    },
                                ],
                            },
                        ],
                    },
                ],
            }),
        ).toStrictEqual([
            {
                field: column_1,
                children: [
                    {
                        identifier: "whatever",
                        columns: [
                            {
                                field: column_0,
                                children: [],
                            },
                        ],
                    },
                ],
            },
            { field: column_0, children: [] },
        ]);
    });
});

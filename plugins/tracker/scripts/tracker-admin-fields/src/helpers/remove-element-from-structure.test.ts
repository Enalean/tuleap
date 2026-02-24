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

import { describe, expect, it } from "vitest";
import { removeElementFromStructure } from "./remove-element-from-structure";
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";

describe("remove-element-from-structure", () => {
    /**
     * ├── 1
     * ├── 2
     * ├── 3
     * ├── 4
     * │   ├── 40
     * │   │   ├── 440
     * │   │   └── 441
     * │   └── 41
     * │       └── 410
     * ├── 5
     * │   ├── 50
     * │   └── 51
     * │       └── empty
     */
    const structure = [
        {
            id: 1,
            content: null,
        },
        {
            id: 2,
            content: null,
        },
        {
            id: 3,
            content: null,
        },
        {
            id: 4,
            content: [
                {
                    id: 40,
                    content: [
                        {
                            id: 440,
                            content: null,
                        },
                        {
                            id: 441,
                            content: null,
                        },
                    ],
                },
                {
                    id: 41,
                    content: [
                        {
                            id: 410,
                            content: null,
                        },
                    ],
                },
            ],
        },
        {
            id: 5,
            content: [
                {
                    id: 50,
                    content: null,
                },
                {
                    id: 51,
                    content: [],
                },
            ],
        },
    ];
    it("remove the field from the root tracker structure", () => {
        const field_to_remove: StructureFields = {
            field_id: 2,
        } as StructureFields;
        const result = removeElementFromStructure(structure, field_to_remove);
        /**
         * ├── 1
         * ├── 2 <--- removed
         * ├── 3
         * ├── 4
         * │   ├── 40
         * │   │   ├── 440
         * │   │   └── 441
         * │   └── 41
         * │       └── 410
         * ├── 5
         * │   ├── 50
         * │   └── 51
         * │       └── empty
         */
        const expected_structure = [
            {
                id: 1,
                content: null,
            },
            {
                id: 3,
                content: null,
            },
            {
                id: 4,
                content: [
                    {
                        id: 40,
                        content: [
                            {
                                id: 440,
                                content: null,
                            },
                            {
                                id: 441,
                                content: null,
                            },
                        ],
                    },
                    {
                        id: 41,
                        content: [
                            {
                                id: 410,
                                content: null,
                            },
                        ],
                    },
                ],
            },
            {
                id: 5,
                content: [
                    {
                        id: 50,
                        content: null,
                    },
                    {
                        id: 51,
                        content: [],
                    },
                ],
            },
        ];
        expect(expected_structure).toStrictEqual(result);
    });
    it("remove a nested form element in tracker structure", () => {
        const field_to_remove: StructureFields = {
            field_id: 440,
        } as StructureFields;
        const result = removeElementFromStructure(structure, field_to_remove);
        /**
         * ├── 1
         * ├── 2
         * ├── 3
         * ├── 4
         * │   ├── 40
         * │   │   ├── 440 <--- removed
         * │   │   └── 441
         * │   └── 41
         * │       └── 410
         * ├── 5
         * │   ├── 50
         * │   └── 51
         * │       └── empty
         */
        const expected_structure = [
            {
                id: 1,
                content: null,
            },
            {
                id: 2,
                content: null,
            },
            {
                id: 3,
                content: null,
            },
            {
                id: 4,
                content: [
                    {
                        id: 40,
                        content: [
                            {
                                id: 441,
                                content: null,
                            },
                        ],
                    },
                    {
                        id: 41,
                        content: [
                            {
                                id: 410,
                                content: null,
                            },
                        ],
                    },
                ],
            },
            {
                id: 5,
                content: [
                    {
                        id: 50,
                        content: null,
                    },
                    {
                        id: 51,
                        content: [],
                    },
                ],
            },
        ];
        expect(expected_structure).toStrictEqual(result);
    });
    it("remove the structural field in tracker structure if empty", () => {
        const field_to_remove: StructureFields = {
            field_id: 51,
        } as StructureFields;
        const result = removeElementFromStructure(structure, field_to_remove);
        /**
         * ├── 1
         * ├── 2
         * ├── 3
         * ├── 4
         * │   ├── 40
         * │   │   ├── 440
         * │   │   └── 441
         * │   └── 41
         * │       └── 410
         * ├── 5
         * │   ├── 50
         * │   └── 51  <--- removed
         * │       └── empty  <--- removed
         */
        const expected_structure = [
            {
                id: 1,
                content: null,
            },
            {
                id: 2,
                content: null,
            },
            {
                id: 3,
                content: null,
            },
            {
                id: 4,
                content: [
                    {
                        id: 40,
                        content: [
                            {
                                id: 440,
                                content: null,
                            },
                            {
                                id: 441,
                                content: null,
                            },
                        ],
                    },
                    {
                        id: 41,
                        content: [
                            {
                                id: 410,
                                content: null,
                            },
                        ],
                    },
                ],
            },
            {
                id: 5,
                content: [
                    {
                        id: 50,
                        content: null,
                    },
                ],
            },
        ];
        expect(expected_structure).toStrictEqual(result);
    });
});

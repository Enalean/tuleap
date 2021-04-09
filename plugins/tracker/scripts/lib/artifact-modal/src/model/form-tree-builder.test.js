/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import { buildFormTree } from "./form-tree-builder.js";

describe("form-tree-builder", () => {
    describe("buildFormTree() -", () => {
        let tracker;

        it(`Given a tracker
                when I build the form tree
                then the form tree given by the tracker structure will be filled with the complete fields,
                augmented with the correct template_url and will be returned`, () => {
            tracker = {
                fields: [
                    { field_id: 1, type: "int" },
                    { field_id: 2, type: "int" },
                    { field_id: 3, type: "fieldset" },
                    { field_id: 4, type: "int" },
                    { field_id: 5, type: "column" },
                    { field_id: 6, type: "int" },
                ],
                structure: [
                    { id: 1, content: null },
                    { id: 2, content: null },
                    {
                        id: 3,
                        content: [
                            { id: 4, content: null },
                            {
                                id: 5,
                                content: [{ id: 6, content: null }],
                            },
                        ],
                    },
                ],
            };

            const output = buildFormTree(tracker);

            expect(output).toEqual([
                {
                    field_id: 1,
                    type: "int",
                    template_url: "field-int.tpl.html",
                },
                {
                    field_id: 2,
                    type: "int",
                    template_url: "field-int.tpl.html",
                },
                {
                    field_id: 3,
                    type: "fieldset",
                    template_url: "field-fieldset.tpl.html",
                    content: [
                        {
                            field_id: 4,
                            type: "int",
                            template_url: "field-int.tpl.html",
                        },
                        {
                            field_id: 5,
                            type: "column",
                            template_url: "field-column.tpl.html",
                            content: [
                                {
                                    field_id: 6,
                                    type: "int",
                                    template_url: "field-int.tpl.html",
                                },
                            ],
                        },
                    ],
                },
            ]);
        });

        it(`Given a tracker
                when I build the form tree
                then the form tree given by the tracker structure will be filled with the complete fields,
                and empty fieldsets will be removed`, () => {
            tracker = {
                fields: [
                    { field_id: 1, type: "int" },
                    { field_id: 2, type: "int" },
                    { field_id: 3, type: "fieldset" },
                ],
                structure: [
                    { id: 1, content: null },
                    { id: 2, content: null },
                    {
                        id: 3,
                        content: [],
                    },
                ],
            };

            const output = buildFormTree(tracker);

            expect(output).toEqual([
                {
                    field_id: 1,
                    type: "int",
                    template_url: "field-int.tpl.html",
                },
                {
                    field_id: 2,
                    type: "int",
                    template_url: "field-int.tpl.html",
                },
            ]);
        });

        it(`Given a tracker
                when I build the form tree
                then the form tree given by the tracker structure will be filled with the complete fields,
                and fieldsets containing only structural fields will be removed`, () => {
            tracker = {
                fields: [
                    { field_id: 1, type: "int" },
                    { field_id: 2, type: "int" },
                    { field_id: 3, type: "fieldset" },
                    { field_id: 4, type: "fieldset" },
                    { field_id: 5, type: "column" },
                ],
                structure: [
                    { id: 1, content: null },
                    { id: 2, content: null },
                    {
                        id: 3,
                        content: [
                            {
                                id: 4,
                                content: [{ id: 5, content: [] }],
                            },
                        ],
                    },
                ],
            };

            const output = buildFormTree(tracker);

            expect(output).toEqual([
                {
                    field_id: 1,
                    type: "int",
                    template_url: "field-int.tpl.html",
                },
                {
                    field_id: 2,
                    type: "int",
                    template_url: "field-int.tpl.html",
                },
            ]);
        });

        it(`Given a structural field, it will add a template_url according to the field type`, () => {
            tracker = {
                fields: [{ field_id: 1, type: "fieldset" }],
                structure: [{ id: 1, content: null }],
            };

            const output = buildFormTree(tracker);

            expect(output).toContainEqual({
                field_id: 1,
                type: "fieldset",
                template_url: "field-fieldset.tpl.html",
            });
        });

        it(`Given a non-structural field, it will add a template_url according to the field type`, () => {
            tracker = {
                fields: [{ field_id: 1, type: "sb" }],
                structure: [{ id: 1, content: null }],
            };

            const output = buildFormTree(tracker);

            expect(output).toContainEqual({
                field_id: 1,
                type: "sb",
                template_url: "field-sb.tpl.html",
            });
        });

        it(`Given an unknown field not in the whitelist, it won't be returned`, () => {
            tracker = {
                fields: [{ field_id: 1, type: "unknown" }],
                structure: [{ id: 1, content: null }],
            };

            const output = buildFormTree(tracker);

            expect(output.length).toBe(0);
        });

        it(`Given an unknown field in a structural field,
            then it won't be output in the structural field's content`, () => {
            tracker = {
                fields: [
                    { field_id: 1, type: "fieldset" },
                    { field_id: 2, type: "unknown" },
                    { field_id: 3, type: "int" },
                ],
                structure: [
                    {
                        id: 1,
                        content: [
                            { id: 2, content: null },
                            { id: 3, content: null },
                        ],
                    },
                ],
            };

            const output = buildFormTree(tracker);

            const fieldset_content = output[0].content;
            expect(fieldset_content).not.toContain(expect.objectContaining({ field_id: 2 }));
            expect(fieldset_content).toContainEqual(expect.objectContaining({ field_id: 3 }));
        });
    });
});

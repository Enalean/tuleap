/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { createVueGettextProviderPassthrough } from "../../../vue-gettext-provider-for-test";
import { DateCell, EmptyCell, NumberCell, TextCell } from "@tuleap/plugin-docgen-xlsx";
import type { BacklogItem, TestDefinition } from "../../../../type";
import { buildRequirementsSection } from "./requirements-builder";
import type { Tracker } from "./Tracker/tracker";
import * as artifacts_retriever from "./Tracker/artifacts-retriever";
import * as trackers_retriever from "./Tracker/trackers-retriever";
import type { Artifact } from "./Tracker/artifact";

describe("Build requirements section", () => {
    it("builds section", async () => {
        const gettext_provider = createVueGettextProviderPassthrough();
        const backlog_items: BacklogItem[] = [
            {
                short_type: "story",
                id: 1,
                label: "Label 1",
                test_definitions: [{ test_status: "passed" }],
            } as BacklogItem,
            {
                short_type: "story",
                id: 2,
                label: "Label 2",
                test_definitions: [{ test_status: "blocked" }],
            } as BacklogItem,
            {
                short_type: "bug",
                id: 3,
                label: "Label 3",
                test_definitions: [{ test_status: "failed" }],
            } as BacklogItem,
            {
                short_type: "bug",
                id: 4,
                label: "Label 4",
                test_definitions: [{ test_status: "notrun" }],
            } as BacklogItem,
            {
                short_type: "bug",
                id: 5,
                label: "Label 5",
                test_definitions: [] as TestDefinition[],
            } as BacklogItem,
        ];

        const story_tracker: Tracker = {
            id: 91,
            fields: [
                {
                    field_id: 191,
                    label: "MyNumber",
                    type: "float",
                },
                {
                    field_id: 291,
                    label: "Computed",
                    type: "computed",
                },
                {
                    field_id: 391,
                    label: "MyNumber",
                    type: "int",
                },
                {
                    field_id: 491,
                    label: "Artifact ID",
                    type: "aid",
                },
                {
                    field_id: 591,
                    label: "Text",
                    type: "text",
                    format: "text",
                },
                {
                    field_id: 691,
                    label: "title",
                    type: "string",
                },
                {
                    field_id: 791,
                    label: "some_date",
                    type: "date",
                },
            ],
            semantics: {
                title: {
                    field_id: 691,
                },
            },
        } as Tracker;
        const bug_tracker: Tracker = {
            id: 92,
            fields: [
                {
                    field_id: 792,
                    label: "MyNumber",
                    type: "float",
                },
            ],
            semantics: {},
        };
        jest.spyOn(artifacts_retriever, "retrieveArtifacts").mockResolvedValue(
            new Map([
                [
                    1,
                    {
                        id: 1,
                        values: [
                            {
                                field_id: 191,
                                label: "MyNumber",
                                type: "int",
                                value: 12,
                            },
                            {
                                field_id: 291,
                                label: "Computed",
                                type: "computed",
                                is_autocomputed: true,
                                value: null,
                                manual_value: null,
                            },
                            {
                                field_id: 391,
                                label: "MyNumber",
                                type: "float",
                                value: 13,
                            },
                            {
                                field_id: 491,
                                label: "Artifact ID",
                                type: "aid",
                                value: null,
                            },
                            {
                                field_id: 591,
                                label: "Text",
                                type: "text",
                                format: "text",
                                value: "Text 1",
                            },
                            {
                                field_id: 691,
                                label: "title",
                                type: "string",
                                value: "Label 1",
                            },
                            {
                                field_id: 691,
                                label: "title",
                                type: "string",
                                value: "Label 1",
                            },
                            {
                                field_id: 791,
                                label: "some_date",
                                type: "date",
                                value: "2020-08-01T00:00:00+01:00",
                            },
                        ],
                        values_by_field: {},
                        tracker: story_tracker,
                    } as Artifact,
                ],
                [
                    2,
                    {
                        id: 2,
                        values: [
                            {
                                field_id: 191,
                                label: "MyNumber",
                                type: "int",
                                value: 22,
                            },
                            {
                                field_id: 291,
                                label: "Computed",
                                type: "computed",
                                is_autocomputed: true,
                                value: 24,
                                manual_value: null,
                            },
                            {
                                field_id: 391,
                                label: "MyNumber",
                                type: "float",
                                value: 23,
                            },
                            {
                                field_id: 491,
                                label: "Artifact ID",
                                type: "aid",
                                value: null,
                            },
                            {
                                field_id: 591,
                                label: "Text",
                                type: "text",
                                format: "text",
                                value: "Text 2",
                            },
                            {
                                field_id: 691,
                                label: "title",
                                type: "string",
                                value: "Label 2",
                            },
                            {
                                field_id: 791,
                                label: "some_date",
                                type: "date",
                                value: null,
                            },
                        ],
                        values_by_field: {},
                        tracker: story_tracker,
                    },
                ],
                [
                    3,
                    {
                        id: 3,
                        values: [
                            {
                                field_id: 792,
                                label: "MyNumber",
                                type: "float",
                                value: 33.33,
                            },
                        ],
                        values_by_field: {},
                        tracker: bug_tracker,
                    },
                ],
                [
                    4,
                    {
                        id: 4,
                        values: [
                            {
                                field_id: 792,
                                label: "MyNumber",
                                type: "float",
                                value: 44.44,
                            },
                        ],
                        values_by_field: {},
                        tracker: bug_tracker,
                    },
                ],
            ]),
        );
        jest.spyOn(trackers_retriever, "retrieveTrackers").mockResolvedValue([
            story_tracker,
            bug_tracker,
        ]);

        const section = await buildRequirementsSection(gettext_provider, backlog_items);

        expect(section).toStrictEqual({
            title: new TextCell("Requirements"),
            headers: [
                new TextCell("Type"),
                new TextCell("ID"),
                new TextCell("Title"),
                new TextCell("Tests status"),
                new TextCell("Computed"),
                new TextCell("MyNumber"),
                new TextCell("some_date"),
                new TextCell("Text"),
            ],
            rows: [
                [
                    new TextCell("story"),
                    new TextCell("1"),
                    new TextCell("Label 1"),
                    new TextCell("Passed"),
                    new EmptyCell(),
                    new NumberCell(12).withComment(
                        "This requirement have multiple fields with this label, only one value is visible",
                    ),
                    new DateCell(new Date("2020-08-01T00:00:00+01:00")),
                    new TextCell("Text 1"),
                ],
                [
                    new TextCell("story"),
                    new TextCell("2"),
                    new TextCell("Label 2"),
                    new TextCell("Blocked"),
                    new NumberCell(24),
                    new NumberCell(22).withComment(
                        "This requirement have multiple fields with this label, only one value is visible",
                    ),
                    new EmptyCell(),
                    new TextCell("Text 2"),
                ],
                [
                    new TextCell("bug"),
                    new TextCell("3"),
                    new TextCell("Label 3"),
                    new TextCell("Failed"),
                    new EmptyCell(),
                    new NumberCell(33.33),
                    new EmptyCell(),
                    new EmptyCell(),
                ],
                [
                    new TextCell("bug"),
                    new TextCell("4"),
                    new TextCell("Label 4"),
                    new TextCell("Not run"),
                    new EmptyCell(),
                    new NumberCell(44.44),
                    new EmptyCell(),
                    new EmptyCell(),
                ],
                [new TextCell("bug"), new TextCell("5"), new TextCell("Label 5"), new TextCell("")],
            ],
        });
    });
});

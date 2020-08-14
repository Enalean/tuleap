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

import { createVueGettextProviderPassthrough } from "../vue-gettext-provider-for-test";
import { EmptyCell, NumberCell, TextCell } from "./report-cells";
import { BacklogItem, TestDefinition } from "../../type";
import { buildRequirementsSection } from "./requirements-builder";
import { Tracker } from "./tracker";
import * as artifacts_retriever from "./artifacts-retriever";
import * as trackers_retriever from "./trackers-retriever";
import { Artifact, TrackerFieldValue } from "./artifact";

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
                    label: "MyNumber",
                    type: "float",
                },
                {
                    label: "Computed",
                    type: "computed",
                },
                {
                    label: "MyNumber",
                    type: "int",
                },
                {
                    label: "Artifact ID",
                    type: "aid",
                },
            ],
        } as Tracker;
        const bug_tracker: Tracker = {
            id: 92,
            fields: [
                {
                    label: "MyNumber",
                    type: "float",
                },
            ],
        };
        jest.spyOn(artifacts_retriever, "retrieveArtifacts").mockResolvedValue(
            new Map([
                [
                    1,
                    {
                        id: 1,
                        values_by_field: {
                            int: {
                                label: "MyNumber",
                                type: "int",
                                value: 12,
                            },
                            computed: {
                                label: "Computed",
                                type: "computed",
                                is_autocomputed: true,
                                value: null,
                            },
                            float: {
                                label: "MyNumber",
                                type: "float",
                                value: 13,
                            },
                            aid: {
                                label: "Artifact ID",
                                type: "aid",
                            } as TrackerFieldValue,
                        },
                        tracker: story_tracker,
                    } as Artifact,
                ],
                [
                    2,
                    {
                        id: 2,
                        values_by_field: {
                            int: {
                                label: "MyNumber",
                                type: "int",
                                value: 22,
                            },
                            computed: {
                                label: "Computed",
                                type: "computed",
                                is_autocomputed: true,
                                value: 24,
                            },
                            float: {
                                label: "MyNumber",
                                type: "float",
                                value: 23,
                            },
                            aid: {
                                label: "Artifact ID",
                                type: "aid",
                            } as TrackerFieldValue,
                        },
                        tracker: story_tracker,
                    },
                ],
                [
                    3,
                    {
                        id: 3,
                        values_by_field: {
                            somefloat: {
                                label: "MyNumber",
                                type: "float",
                                value: 33.33,
                            },
                        },
                        tracker: bug_tracker,
                    },
                ],
                [
                    4,
                    {
                        id: 4,
                        values_by_field: {
                            somefloat: {
                                label: "MyNumber",
                                type: "float",
                                value: 44.44,
                            },
                        },
                        tracker: bug_tracker,
                    },
                ],
            ])
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
            ],
            rows: [
                [
                    new TextCell("story"),
                    new TextCell("1"),
                    new TextCell("Label 1"),
                    new TextCell("Passed"),
                    new EmptyCell(),
                    new NumberCell(12).withComment(
                        "This requirement have multiple fields with this label, only one value is visible"
                    ),
                ],
                [
                    new TextCell("story"),
                    new TextCell("2"),
                    new TextCell("Label 2"),
                    new TextCell("Blocked"),
                    new NumberCell(24),
                    new NumberCell(22).withComment(
                        "This requirement have multiple fields with this label, only one value is visible"
                    ),
                ],
                [
                    new TextCell("bug"),
                    new TextCell("3"),
                    new TextCell("Label 3"),
                    new TextCell("Failed"),
                    new EmptyCell(),
                    new NumberCell(33.33),
                ],
                [
                    new TextCell("bug"),
                    new TextCell("4"),
                    new TextCell("Label 4"),
                    new TextCell("Not run"),
                    new EmptyCell(),
                    new NumberCell(44.44),
                ],
                [new TextCell("bug"), new TextCell("5"), new TextCell("Label 5"), new TextCell("")],
            ],
        });
    });
});

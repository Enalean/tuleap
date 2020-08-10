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
import { TextCell } from "./report-cells";
import { BacklogItem, TestDefinition } from "../../type";
import { buildRequirementsSection } from "./requirements-builder";

describe("Build requirements section", () => {
    it("builds section", () => {
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

        const section = buildRequirementsSection(gettext_provider, backlog_items);

        expect(section).toStrictEqual({
            title: new TextCell("Requirements"),
            headers: [
                new TextCell("Type"),
                new TextCell("ID"),
                new TextCell("Title"),
                new TextCell("Tests status"),
            ],
            rows: [
                [
                    new TextCell("story"),
                    new TextCell("1"),
                    new TextCell("Label 1"),
                    new TextCell("Passed"),
                ],
                [
                    new TextCell("story"),
                    new TextCell("2"),
                    new TextCell("Label 2"),
                    new TextCell("Blocked"),
                ],
                [
                    new TextCell("bug"),
                    new TextCell("3"),
                    new TextCell("Label 3"),
                    new TextCell("Failed"),
                ],
                [
                    new TextCell("bug"),
                    new TextCell("4"),
                    new TextCell("Label 4"),
                    new TextCell("Not run"),
                ],
                [new TextCell("bug"), new TextCell("5"), new TextCell("Label 5"), new TextCell("")],
            ],
        });
    });
});

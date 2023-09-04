/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import {
    buildCellContentResult,
    buildTableCellContent,
    buildTableCellLabel,
} from "./table-builder";
import type { IContext } from "docx";
import { TextRun } from "docx";
import type { ArtifactFieldValueStatus } from "@tuleap/plugin-docgen-docx";
import { createGettextProviderPassthrough } from "../../../../create-gettext-provider-passthrough-for-tests";

describe("table-builder", () => {
    describe("buildTableCellLabel", () => {
        it("builds a table cell with a given label", () => {
            const table_cell = buildTableCellLabel("Lorem ipsum");

            const tree = table_cell.prepForXml({} as IContext);
            expect(JSON.stringify(tree)).toContain("Lorem ipsum");
            expect(JSON.stringify(tree)).toContain("table_label");
        });
    });
    describe("buildTableCellContent", () => {
        it("builds a table cell with a given content", () => {
            const table_cell = buildTableCellContent(new TextRun("Lorem ipsum"));

            const tree = table_cell.prepForXml({} as IContext);
            expect(JSON.stringify(tree)).toContain("Lorem ipsum");
            expect(JSON.stringify(tree)).toContain("table_value");
        });
    });
    describe("buildCellContentResult", () => {
        const cases: ReadonlyArray<[ArtifactFieldValueStatus, string]> = [
            [null, ""],
            ["notrun", "Not run"],
            ["passed", "Passed"],
            ["failed", "Failed"],
            ["blocked", "Blocked"],
        ];

        it.each(cases)(
            `when status is %s then displayed label is %s`,
            (status: ArtifactFieldValueStatus, expected_label) => {
                const table_cell = buildCellContentResult(
                    status,
                    createGettextProviderPassthrough(),
                    1,
                );

                const tree = table_cell.prepForXml({} as IContext);
                expect(JSON.stringify(tree)).toContain(expected_label);
            },
        );
    });
});

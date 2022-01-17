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

import { buildTableCellContent, buildTableCellLabel } from "./table-builder";
import type { IContext } from "docx";
import { TextRun } from "docx";

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
});

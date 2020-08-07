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

import { TextCell } from "./report-cells";
import { ExportReport } from "./report-creator";
import { transformAReportIntoASheet } from "./transform-report-to-xlsx-sheet";

describe("Transform an export report into a XSLX sheet", () => {
    it("builds an XSLX sheet with proper column widths", () => {
        const report: ExportReport = {
            sections: [
                {
                    rows: [[new TextCell("Section A")]],
                },
                {
                    rows: [[new TextCell("Section B"), new TextCell("Section B col 2")]],
                },
            ],
        };

        const sheet = transformAReportIntoASheet(report);

        expect(sheet).toMatchObject({
            "!cols": [
                {
                    wch: 9,
                },
                {
                    wch: 15,
                },
            ],
            "!ref": "A1:B2",
            A1: {
                t: "s",
                v: "Section A",
            },
            A2: {
                t: "s",
                v: "Section B",
            },
            B2: {
                t: "s",
                v: "Section B col 2",
            },
        });
    });
});

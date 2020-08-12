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

import { DateCell, HTMLCell, TextCell } from "./report-cells";
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
                    title: new TextCell("Section B"),
                    headers: [new TextCell("Col 1"), new TextCell("Col 2")],
                    rows: [
                        [new TextCell("Section B"), new TextCell("Section B col 2")],
                        [new DateCell(new Date("2020-09-07T14:00:00.000Z"))],
                        [new HTMLCell("<div>HTML Content</div>")],
                    ],
                },
            ],
        };

        const sheet = transformAReportIntoASheet(report);

        expect(sheet).toMatchInlineSnapshot(`
            Object {
              "!cols": Array [
                Object {
                  "wch": 12,
                },
                Object {
                  "wch": 15,
                },
              ],
              "!merges": Array [
                Object {
                  "e": Object {
                    "c": 1,
                    "r": 2,
                  },
                  "s": Object {
                    "c": 0,
                    "r": 2,
                  },
                },
              ],
              "!ref": "A1:B8",
              "A1": Object {
                "character_width": 9,
                "t": "s",
                "v": "Section A",
              },
              "A2": Object {
                "character_width": 0,
                "t": "z",
              },
              "A3": Object {
                "character_width": 9,
                "merge_columns": 1,
                "t": "s",
                "v": "Section B",
              },
              "A4": Object {
                "character_width": 5,
                "t": "s",
                "v": "Col 1",
              },
              "A5": Object {
                "character_width": 9,
                "t": "s",
                "v": "Section B",
              },
              "A6": Object {
                "character_width": 10,
                "t": "d",
                "v": 2020-09-07T14:00:00.000Z,
              },
              "A7": Object {
                "character_width": 12,
                "t": "s",
                "v": "HTML Content",
              },
              "A8": Object {
                "character_width": 0,
                "t": "z",
              },
              "B4": Object {
                "character_width": 5,
                "t": "s",
                "v": "Col 2",
              },
              "B5": Object {
                "character_width": 15,
                "t": "s",
                "v": "Section B col 2",
              },
            }
        `);
    });

    it("builds a sheet with an empty row", () => {
        const report: ExportReport = {
            sections: [
                {
                    rows: [[]],
                },
            ],
        };

        const sheet = transformAReportIntoASheet(report);

        expect(sheet).toMatchObject({ "!merges": [] });
    });
});

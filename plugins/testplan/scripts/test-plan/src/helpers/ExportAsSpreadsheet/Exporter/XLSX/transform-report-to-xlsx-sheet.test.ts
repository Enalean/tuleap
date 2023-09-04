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

import { DateCell, EmptyCell, HTMLCell, NumberCell, TextCell } from "@tuleap/plugin-docgen-xlsx";
import type { ExportReport } from "../../Report/report-creator";
import { transformAReportIntoASheet } from "./transform-report-to-xlsx-sheet";

describe("Transform an export report into a XSLX sheet", () => {
    it("builds an XSLX sheet with proper column widths", () => {
        const report: ExportReport = {
            sections: [
                {
                    rows: [[new TextCell("Section A")]],
                },
                {
                    title: new TextCell("Section B with a very very very very very long title"),
                    headers: [new TextCell("Col 1"), new TextCell("Col 2")],
                    rows: [
                        [new TextCell("Section B"), new TextCell("Section B col 2")],
                        [new DateCell(new Date("2020-09-07T14:00:00.000Z"))],
                        [new HTMLCell("<div>HTML Content</div>")],
                        [new EmptyCell()],
                        [new NumberCell(2020).withComment("A comment")],
                        [
                            new EmptyCell(),
                            new EmptyCell(),
                            new TextCell(
                                "I'm a very (very very very very very very very very very very very very very very very very very very very very very very very very very) long text line\n",
                            ),
                            new TextCell("I'm a text line\r\nwith\r\nsome\r\nline breaks\r\n"),
                        ],
                    ],
                },
            ],
        };

        const sheet = transformAReportIntoASheet(report);

        // Make an exception for XLSX output
        // eslint-disable-next-line jest/no-large-snapshots
        expect(sheet).toMatchInlineSnapshot(`
            {
              "!cols": [
                {
                  "wch": 12,
                },
                {
                  "wch": 15,
                },
                {
                  "wch": 65,
                },
                {
                  "wch": 15,
                },
              ],
              "!merges": [
                {
                  "e": {
                    "c": 1,
                    "r": 2,
                  },
                  "s": {
                    "c": 0,
                    "r": 2,
                  },
                },
              ],
              "!ref": "A1:D11",
              "!rows": [
                {},
                {},
                {},
                {},
                {},
                {},
                {},
                {},
                {},
                {
                  "hpt": 48,
                },
                {},
              ],
              "A1": {
                "character_width": 9,
                "nb_lines": 1,
                "t": "s",
                "v": "Section A",
              },
              "A10": {
                "character_width": 0,
                "nb_lines": 1,
                "t": "z",
              },
              "A11": {
                "character_width": 0,
                "nb_lines": 1,
                "t": "z",
              },
              "A2": {
                "character_width": 0,
                "nb_lines": 1,
                "t": "z",
              },
              "A3": {
                "character_width": 10,
                "merge_columns": 1,
                "nb_lines": 1,
                "t": "s",
                "v": "Section B with a very very very very very long title",
              },
              "A4": {
                "character_width": 5,
                "nb_lines": 1,
                "t": "s",
                "v": "Col 1",
              },
              "A5": {
                "character_width": 9,
                "nb_lines": 1,
                "t": "s",
                "v": "Section B",
              },
              "A6": {
                "character_width": 10,
                "nb_lines": 1,
                "t": "d",
                "v": 2020-09-07T14:00:00.000Z,
              },
              "A7": {
                "character_width": 12,
                "nb_lines": 1,
                "t": "s",
                "v": "HTML Content",
              },
              "A8": {
                "character_width": 0,
                "nb_lines": 1,
                "t": "z",
              },
              "A9": {
                "c": [
                  {
                    "t": "A comment",
                  },
                ],
                "character_width": 4,
                "nb_lines": 1,
                "t": "n",
                "v": 2020,
              },
              "B10": {
                "character_width": 0,
                "nb_lines": 1,
                "t": "z",
              },
              "B4": {
                "character_width": 5,
                "nb_lines": 1,
                "t": "s",
                "v": "Col 2",
              },
              "B5": {
                "character_width": 15,
                "nb_lines": 1,
                "t": "s",
                "v": "Section B col 2",
              },
              "C10": {
                "character_width": 152,
                "nb_lines": 1,
                "t": "s",
                "v": "I'm a very (very very very very very very very very very very very very very very very very very very very very very very very very very) long text line",
              },
              "D10": {
                "character_width": 15,
                "nb_lines": 4,
                "t": "s",
                "v": "I'm a text line
            with
            some
            line breaks",
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

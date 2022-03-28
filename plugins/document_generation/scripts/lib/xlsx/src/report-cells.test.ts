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

import { DateCell, EmptyCell, HTMLCell, NumberCell, TextCell } from "./report-cells";

describe("Report cells", () => {
    it("has a plaintext cell", () => {
        const cell = new TextCell("some text");

        expect(cell).toEqual({ type: "text", value: "some text" });
    });

    it("has a HTML cell", () => {
        const cell = new HTMLCell("<p>Some HTML</p>");

        expect(cell).toEqual({ type: "html", value: "<p>Some HTML</p>" });
    });

    it("has a date cell", () => {
        const date = new Date(2020, 8, 7, 17, 15, 0);
        const cell = new DateCell(date);

        expect(cell).toEqual({ type: "date", value: date });
    });

    it("has a number cell", () => {
        const n = 2020;
        const cell = new NumberCell(n);

        expect(cell).toEqual({ type: "number", value: n });
    });

    it("has an empty cell", () => {
        const cell = new EmptyCell();

        expect(cell).toEqual({ type: "empty" });
    });

    it("can have a comment", () => {
        const cell = new TextCell("Text");
        const cell_with_comment = cell.withComment("A comment").withComment("A comment 2");

        expect(cell.comment).toBe(undefined);
        expect(cell_with_comment).toEqual({ type: "text", value: "Text", comment: "A comment 2" });
    });
});

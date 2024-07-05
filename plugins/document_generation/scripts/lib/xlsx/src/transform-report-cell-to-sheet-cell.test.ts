/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import { DateCell } from "./report-cells";
import { transformReportCellIntoASheetCell } from "./transform-report-cell-to-sheet-cell";

describe("Transform report cell into a sheet cell", () => {
    it("transform date cell", () => {
        const date_report_cell = new DateCell("2024-07-05T10:11:00+02:00");

        const sheet_cell = transformReportCellIntoASheetCell(date_report_cell);

        expect(sheet_cell).toStrictEqual({
            character_width: 10,
            nb_lines: 1,
            t: "d",
            v: "2024-07-05T10:11:00",
        });
    });
});

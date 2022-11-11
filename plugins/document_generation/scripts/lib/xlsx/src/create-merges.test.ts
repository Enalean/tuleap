/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import { createMerges, createMergesForWholeRowLine } from "./create-merges";
import type { CellObjectWithExtraInfo } from "./type";

describe("create-merges", () => {
    it("creates merges for the first cell only", () => {
        const merges = createMerges(buildCollectionOfCellObjectWithExtraInfo());

        expect(merges).toStrictEqual([
            {
                s: { r: 0, c: 0 },
                e: { r: 0, c: 3 },
            },
        ]);
    });
    it("creates merges for the all cells", () => {
        const merges = createMergesForWholeRowLine(buildCollectionOfCellObjectWithExtraInfo());

        expect(merges).toStrictEqual([
            {
                e: { c: 3, r: 0 },
                s: { c: 0, r: 0 },
            },
            {
                e: { c: 6, r: 0 },
                s: { c: 4, r: 0 },
            },
        ]);
    });
});

function buildCollectionOfCellObjectWithExtraInfo(): Array<Array<CellObjectWithExtraInfo>> {
    return [
        [
            {
                t: "s",
                v: "Text 01",
                character_width: 20,
                nb_lines: 1,
                merge_columns: 3,
            },
            {
                t: "z",
                character_width: 0,
                nb_lines: 1,
            },

            {
                t: "z",
                character_width: 0,
                nb_lines: 1,
            },
            {
                t: "z",
                character_width: 0,
                nb_lines: 1,
            },
            {
                t: "s",
                v: "Text 02",
                character_width: 20,
                nb_lines: 1,
                merge_columns: 2,
            },
            {
                t: "z",
                character_width: 0,
                nb_lines: 1,
            },
            {
                t: "z",
                character_width: 0,
                nb_lines: 1,
            },
        ],
    ];
}

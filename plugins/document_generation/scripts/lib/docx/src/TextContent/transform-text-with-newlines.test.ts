/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
import { transformTextWithNewlines } from "./transform-text-with-newlines";
import { TextRun } from "docx";

describe("transform-text-with-newlines", () => {
    it("transforms text and preserve new lines", () => {
        const runs = transformTextWithNewlines("A\nB", { bold: true });

        expect(runs).toStrictEqual([
            new TextRun({ text: "A", bold: true }),
            new TextRun({ text: "B", break: 1, bold: true }),
        ]);
    });
});

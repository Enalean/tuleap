/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

import { compare } from "./string.js";

describe("String:", () => {
    describe("compare", () => {
        it("handles undefined as greater than other strings", () => {
            expect(compare(undefined, "a")).toBe(1);
            expect(compare("a", undefined)).toBe(-1);
        });
        it("handles nulls as greater than orther strings", () => {
            expect(compare(null, "a")).toBe(1);
            expect(compare("a", null)).toBe(-1);
        });
        it("compares strings with lexicographical order", () => {
            expect(compare("first", "second")).toBe(-1);
            expect(compare("second", "first")).toBe(1);
        });
        it("ignores cases", () => {
            expect(compare("A", "b")).toBe(-1);
            expect(compare("b", "A")).toBe(1);
        });
        it("handles same object", () => {
            const string = "a";
            expect(compare(string, string)).toBe(0);
        });
        it("handles numbers", () => {
            expect(compare("v2", "v10")).toBe(-1);
            expect(compare("v10", "v2")).toBe(1);
        });
    });
});

/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { describe, expect, it } from "@jest/globals";
import { isMatchingFilterValue } from "./is-matching-filter-value";

describe("isMatchingFilterValue", () => {
    it("should return false if strinq is null", () => {
        expect(isMatchingFilterValue(null, "foobar")).toBe(false);
    });

    it("should return false if string is empty", () => {
        expect(isMatchingFilterValue("", "foobar")).toBe(false);
    });

    it("should return false if string does not contain keywwords", () => {
        expect(isMatchingFilterValue("Lorem ipsum", "foobar")).toBe(false);
    });

    it("should return true if string contains keywords", () => {
        expect(isMatchingFilterValue("Lorem foobar ipsum", "foobar")).toBe(true);
    });

    it("should return true if keywords is empty", () => {
        expect(isMatchingFilterValue("Lorem foobar ipsum", "")).toBe(true);
    });

    it("should ignore case", () => {
        expect(isMatchingFilterValue("Lorem FooBAr ipsum", "fOoBaR")).toBe(true);
    });

    it("should return true if the string contains at least one keyword", () => {
        expect(isMatchingFilterValue("Lorem foobar ipsum", "nomatch foobar")).toBe(true);
    });

    it("should return false if the string does not match and keyword contains multiple spaces", () => {
        expect(isMatchingFilterValue("Lorem ipsum", "foobar  ")).toBe(false);
    });

    it("should return true for a partial match", () => {
        expect(isMatchingFilterValue("Brainstorming", "in")).toBe(true);
    });

    it("should return false if there is a partial match but there is more than one keyword", () => {
        expect(isMatchingFilterValue("Brainstorming", "search in trackers")).toBe(false);
    });

    it.each("\\^$.*+?()[]{}|".split(""))(
        "should escape special characters to not break the RegExp",
        (special_char) => {
            expect(
                isMatchingFilterValue(
                    `Lorem fo${special_char}obar ipsum`,
                    `nomatch fo${special_char}obar`,
                ),
            ).toBe(true);
        },
    );
});

/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import {
    getRelativeDateUserPreferenceOrThrow,
    relativeDatePlacement,
    relativeDatePreference,
} from "./relative-date-helper";
import {
    OTHER_PLACEMENT_RIGHT,
    OTHER_PLACEMENT_TOOLTIP,
    OTHER_PLACEMENT_TOP,
    SHOW_ABSOLUTE_DATE,
    SHOW_RELATIVE_DATE,
} from "./relative-date-element";

describe("relative-date helpers", () => {
    it("tests relativeDatePreference", () => {
        expect(relativeDatePreference("absolute_first-relative_shown")).toBe(SHOW_ABSOLUTE_DATE);
        expect(relativeDatePreference("absolute_first-relative_tooltip")).toBe(SHOW_ABSOLUTE_DATE);
        expect(relativeDatePreference("relative_first-absolute_shown")).toBe(SHOW_RELATIVE_DATE);
        expect(relativeDatePreference("relative_first-absolute_tooltip")).toBe(SHOW_RELATIVE_DATE);
    });

    it("tests relativeDatePlacement", () => {
        expect(relativeDatePlacement("absolute_first-relative_shown", "top")).toBe(
            OTHER_PLACEMENT_TOP,
        );
        expect(relativeDatePlacement("absolute_first-relative_tooltip", "top")).toBe(
            OTHER_PLACEMENT_TOOLTIP,
        );
        expect(relativeDatePlacement("relative_first-absolute_shown", "top")).toBe(
            OTHER_PLACEMENT_TOP,
        );
        expect(relativeDatePlacement("relative_first-absolute_tooltip", "top")).toBe(
            OTHER_PLACEMENT_TOOLTIP,
        );

        expect(relativeDatePlacement("absolute_first-relative_shown", "right")).toBe(
            OTHER_PLACEMENT_RIGHT,
        );
        expect(relativeDatePlacement("absolute_first-relative_tooltip", "right")).toBe(
            OTHER_PLACEMENT_TOOLTIP,
        );
        expect(relativeDatePlacement("relative_first-absolute_shown", "right")).toBe(
            OTHER_PLACEMENT_RIGHT,
        );
        expect(relativeDatePlacement("relative_first-absolute_tooltip", "right")).toBe(
            OTHER_PLACEMENT_TOOLTIP,
        );
    });

    describe(`getRelativeDateUserPreferenceOrThrow()`, () => {
        it(`reads the given attribute name from the given element and returns the user preference string`, () => {
            const doc = document.implementation.createHTMLDocument();
            const element = doc.createElement("div");
            element.setAttribute("data-user-pref", "absolute_first-relative_tooltip");
            expect(getRelativeDateUserPreferenceOrThrow(element, "data-user-pref")).toBe(
                "absolute_first-relative_tooltip",
            );
        });

        it(`throws an error when the given element has no attribute with the given attribute name`, () => {
            const doc = document.implementation.createHTMLDocument();
            const element = doc.createElement("div");
            expect(() => getRelativeDateUserPreferenceOrThrow(element, "data-user-pref")).toThrow();
        });
    });
});

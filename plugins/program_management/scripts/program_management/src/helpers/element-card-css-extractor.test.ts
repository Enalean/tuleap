/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { getAccessibilityClasses, showAccessibilityPattern } from "./element-card-css-extractor";
import type { ElementWithAccessibility, TrackerMinimalRepresentation } from "../type";

describe("css extractor", () => {
    describe("getAccessibilityClasses", () => {
        it("Returns card background color", () => {
            const element = {
                tracker: {
                    color_name: "fiesta-red",
                } as TrackerMinimalRepresentation,
                background_color: "lake_placid_blue",
            } as ElementWithAccessibility;
            const classes = getAccessibilityClasses(element, false);
            expect(classes).toEqual([
                "element-card-fiesta-red",
                "element-card-background-lake_placid_blue",
            ]);
        });

        it("Returns accessibility class", () => {
            const element = {
                tracker: {
                    color_name: "fiesta-red",
                } as TrackerMinimalRepresentation,
                background_color: "lake_placid_blue",
            } as ElementWithAccessibility;
            const classes = getAccessibilityClasses(element, true);
            expect(classes).toEqual([
                "element-card-fiesta-red",
                "element-card-background-lake_placid_blue",
                "element-card-with-accessibility",
            ]);
        });

        it("Returns no background and no accessibility classes", () => {
            const element = {
                tracker: {
                    color_name: "fiesta-red",
                } as TrackerMinimalRepresentation,
                background_color: "",
            } as ElementWithAccessibility;
            const classes = getAccessibilityClasses(element, false);
            expect(classes).toEqual(["element-card-fiesta-red"]);
        });
    });

    describe("showAccessibilityPattern", () => {
        it("Returns false when user does not have accessibility option", () => {
            const element = {
                background_color: "lake_placid_blue",
            } as ElementWithAccessibility;
            expect(showAccessibilityPattern(element, false)).toBeFalsy();
        });

        it("Returns false when background color is not set", () => {
            const element = {
                background_color: "",
            } as ElementWithAccessibility;
            expect(showAccessibilityPattern(element, true)).toBeFalsy();
        });

        it("Returns true when accessibility can be displayed", () => {
            const element = {
                background_color: "lake_placid_blue",
            } as ElementWithAccessibility;
            expect(showAccessibilityPattern(element, true)).toBeTruthy();
        });
    });
});

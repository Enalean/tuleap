/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { describe, expect, it } from "vitest";
import { getHeadingDropdownClass, getTextStyleDropdownMenu } from "./text-style-dropdown-menu";
import { schema } from "prosemirror-schema-basic";
import type { GetText } from "@tuleap/gettext";

describe("Text style menu", () => {
    const gettext_provider: GetText = {
        gettext(value: string): string {
            return value;
        },
    } as GetText;
    describe("getHeadingDropdownClass", () => {
        it("should return correct className", () => {
            expect(getHeadingDropdownClass("1")).toBe("heading_dropdown_1");
        });
    });
    describe("getTextStyleDropdownMenu", () => {
        it("should return a dropdown item", () => {
            expect(getTextStyleDropdownMenu(schema, "1", gettext_provider)).toBeDefined();
        });
    });
});

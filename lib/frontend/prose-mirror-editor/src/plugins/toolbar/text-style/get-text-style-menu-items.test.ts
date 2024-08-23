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

import { describe, expect, it, vi } from "vitest";
import { getHeadingMenuItems, getPlainTextMenuItem } from "./get-text-style-menu-items";
import { schema } from "prosemirror-schema-basic";
import type { GetText } from "@tuleap/gettext";

describe("GetTextStyleMenuItems", () => {
    const gettext_provider: GetText = {
        gettext(value: string): string {
            return value;
        },
    } as GetText;
    describe("getPlainTextMenuItem", () => {
        it("should return a plain text menu item", () => {
            const menu_item = getPlainTextMenuItem(schema, vi.fn(), gettext_provider);
            expect(menu_item).toBeDefined();
            expect(menu_item.spec.label).toBe("Normal text");
            expect(menu_item.spec.title).toBe("Change to plain text");
        });
    });
    describe("getHeadingMenuItems", () => {
        it("should return a list of heading menu items", () => {
            const menu_item = getHeadingMenuItems(schema, 4, vi.fn(), gettext_provider);
            expect(menu_item).toHaveLength(4);
            expect(menu_item[0].spec.label).toBe("Title 1");
            expect(menu_item[0].spec.title).toBe("Change to heading 1");
        });
    });
});

/*
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { getOpenStaticValue } from "./open-static-list-value-getter";

describe("open-static-list-value-getter", () => {
    describe("getOpenStaticValue", () => {
        it("returns the given item when this item is an open list static value", () => {
            const item_value = {
                label: "my value",
                color: "",
                id: 10,
                is_hidden: false,
            };
            const open_static_value = getOpenStaticValue(item_value);
            expect(open_static_value).toBe(open_static_value);
        });

        const item_without_label = {
            color: "clockwork-orange",
            id: 10,
            is_hidden: false,
        };
        const item_without_color = {
            label: "my value",
            is_hidden: false,
        };
        it.each([
            ["is not an object", 12],
            ["has not the 'label' key", item_without_label],
            ["has not the 'color' key", item_without_color],
            ["is null", null],
        ])(`returns null when the given item %s`, (_given_item_type, item_value) => {
            expect(getOpenStaticValue(item_value)).toBe(null);
        });
    });
});

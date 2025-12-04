/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
import { hasColorValue } from "./color-helper";
import type { StaticListItem } from "@tuleap/plugin-tracker-rest-api-types";
import { StaticListItemTestBuilder } from "../tests/builders/StaticListItemTestBuilder";

describe("color-helper", () => {
    describe("hasColorValue", () => {
        it("returns true if the static list has a color value", () => {
            const value: StaticListItem = StaticListItemTestBuilder.aStaticListItem(1)
                .withColor("red-wine")
                .build();
            expect(hasColorValue(value)).toBe(true);
        });
        it("returns false if the static list has a no color value", () => {
            const value: StaticListItem = StaticListItemTestBuilder.aStaticListItem(1).build();
            expect(hasColorValue(value)).toBe(false);
        });
    });
});

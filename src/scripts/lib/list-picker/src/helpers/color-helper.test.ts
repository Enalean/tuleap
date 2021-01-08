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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { convertBadColorHexToRGB, isColorBad } from "./color-helper";

describe("color-helper", () => {
    describe("isColorBad", () => {
        it("returns true if the color is an hexadecimal value", () => {
            expect(isColorBad("#f0f1f8")).toBe(true);
        });

        it("returns false if the color is not a hexadecimal value", () => {
            expect(isColorBad("inca-silver")).toBe(false);
        });
    });
    describe("convertBadColorHexToRGB", () => {
        it("returns null if the given color is not in hexa", () => {
            expect(convertBadColorHexToRGB("#Nurb")).toBeNull();
        });
        it("returns the rgb code if the given color is in hexa", () => {
            expect(convertBadColorHexToRGB("#f0f1f8")).toEqual({ blue: 248, green: 241, red: 240 });
        });
    });
});

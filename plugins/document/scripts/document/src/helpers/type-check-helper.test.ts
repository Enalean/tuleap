/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../constants";
import { isOtherType } from "./type-check-helper";
import type { Item } from "../type";

describe("type-check-helper", () => {
    describe("isOtherType", () => {
        it("should return true if item is an unknown type", () => {
            const item = {
                type: "whatever",
            } as Item;
            expect(isOtherType(item)).toBe(true);
        });

        it.each([
            [TYPE_FILE],
            [TYPE_EMPTY],
            [TYPE_LINK],
            [TYPE_WIKI],
            [TYPE_EMBEDDED],
            [TYPE_FOLDER],
        ])("should return false if item is %s", (type: string) => {
            const item = {
                type,
            } as Item;
            expect(isOtherType(item)).toBe(false);
        });
    });
});

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

import { describe, expect, it } from "vitest";
import type { Item } from "../type";
import { canDelete } from "./can-delete-helper";

describe("canDeleteProperties", () => {
    it.each<[boolean, Pick<Item, "user_can_write" | "can_user_manage">, boolean]>([
        [false, { user_can_write: false, can_user_manage: false }, false],
        [false, { user_can_write: false, can_user_manage: true }, false],
        [false, { user_can_write: true, can_user_manage: false }, true],
        [false, { user_can_write: true, can_user_manage: true }, true],
        [true, { user_can_write: false, can_user_manage: false }, false],
        [true, { user_can_write: false, can_user_manage: true }, true],
        [true, { user_can_write: true, can_user_manage: false }, false],
        [true, { user_can_write: true, can_user_manage: true }, true],
    ])(
        "Given configuration is %s and item is %s then expected result is %s",
        (forbid_writers_to_delete, item, expected) => {
            expect(canDelete(forbid_writers_to_delete, item as Item)).toBe(expected);
        },
    );
});

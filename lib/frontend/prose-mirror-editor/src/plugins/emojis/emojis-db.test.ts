/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import { getEmojiDB } from "./emojis-db";

describe("getEmojiDB", () => {
    it("Build cache on first call and use it for all others calls", () => {
        const keys = vi.spyOn(Object, "keys");

        const first_results = getEmojiDB();

        getEmojiDB();
        getEmojiDB();
        getEmojiDB();

        expect(getEmojiDB()).toStrictEqual(first_results);
        expect(keys).toHaveBeenCalledOnce();
    });
});

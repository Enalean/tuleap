/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { LazyboxItemStub } from "../../tests/stubs/LazyboxItemStub";
import type { LazyboxItem } from "../GroupCollection";
import { Selection } from "./Selection";

describe("Selection", () => {
    let callback: (item: LazyboxItem) => void;

    beforeEach(() => {
        callback = vi.fn();
    });

    describe("selectItem()", () => {
        it("should not call the callback when the item is disabled", () => {
            Selection(callback).selectItem(LazyboxItemStub.withDefaults({ is_disabled: true }));

            expect(callback).not.toHaveBeenCalled();
        });

        it("should call the callback when an item is selected", () => {
            const item = LazyboxItemStub.withDefaults({ is_disabled: false });

            Selection(callback).selectItem(item);

            expect(callback).toHaveBeenCalledOnce();
            expect(callback).toHaveBeenCalledWith(item);
        });
    });

    it("isSelected() should always return false", () => {
        expect(Selection(callback).isSelected(LazyboxItemStub.withDefaults())).toBe(false);
    });
});

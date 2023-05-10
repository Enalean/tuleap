/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import { findLabelMatchingValue, findLabelsWithIds } from "./LabelFinder";
import { LazyboxItemStub } from "../../../../tests/stubs/LazyboxItemStub";

describe("LabelFinder", () => {
    it("findLabelsWithIds() should return a list of the LazyboxItems containing labels appearing in the provided list", () => {
        const item_101 = LazyboxItemStub.withValue({ id: 101 });
        const item_102 = LazyboxItemStub.withValue({ id: 102 });
        const item_103 = LazyboxItemStub.withValue({ id: 103 });
        const item_104 = LazyboxItemStub.withValue({ id: 104 });

        const items = findLabelsWithIds([item_101, item_102, item_103, item_104], [102, 104]);

        expect(items).toStrictEqual([item_102, item_104]);
    });

    it("findLabelMatchingValue() should return a list of the LazyboxItems containing labels matching the provided value", () => {
        const item_1 = LazyboxItemStub.withValue({ id: 101, label: "Emergency" });
        const item_2 = LazyboxItemStub.withValue({ id: 102, label: "Easy fix" });
        const item_3 = LazyboxItemStub.withValue({ id: 103, label: "Quick feature" });

        const items = findLabelMatchingValue([item_1, item_2, item_3], "ea");

        expect(items).toStrictEqual([item_2, item_3]);
    });
});

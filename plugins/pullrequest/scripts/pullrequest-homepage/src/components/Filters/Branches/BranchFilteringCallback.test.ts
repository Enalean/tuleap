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

import { describe, it, expect } from "vitest";
import { BranchFilteringCallback } from "./BranchFilteringCallback";

const walnut_branch = {
    value: { name: "walnut" },
    is_disabled: false,
};
const baobab_branch = {
    value: { name: "baobab" },
    is_disabled: false,
};
const mahogany_branch = {
    value: { name: "mahogany" },
    is_disabled: false,
};

describe("BranchFilteringCallback", () => {
    it("Given an empty query and a collection of LazyboxItem, then it should return all the items", () => {
        const filtered_branches = BranchFilteringCallback("", [
            walnut_branch,
            baobab_branch,
            mahogany_branch,
        ]);

        expect(filtered_branches).toHaveLength(3);
        expect(filtered_branches).toStrictEqual([walnut_branch, baobab_branch, mahogany_branch]);
    });

    it("Given a query and a collection of LazyboxItem, Then it should only return the items matching with the query", () => {
        const filtered_branches = BranchFilteringCallback("Mah", [
            walnut_branch,
            baobab_branch,
            mahogany_branch,
        ]);

        expect(filtered_branches).toHaveLength(1);
        expect(filtered_branches).toStrictEqual([mahogany_branch]);
    });
});

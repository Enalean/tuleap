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
import { GettextStub } from "../../../../tests/stubs/GettextStub";
import { TargetBranchFilterBuilder, TYPE_FILTER_TARGET_BRANCH } from "./TargetBranchFilter";

describe("TargetBranchFilter", () => {
    it("Given a branch, then it should return a TargetBranchFilter", () => {
        const builder = TargetBranchFilterBuilder(GettextStub);
        const branch = { name: "walnut" };
        const filter = builder.fromBranch(branch);

        expect(filter.type).toBe(TYPE_FILTER_TARGET_BRANCH);
        expect(filter.label).toBe(`Branch: ${branch.name}`);
        expect(filter.value).toBe(branch);
        expect(filter.is_unique).toBe(true);
    });
});

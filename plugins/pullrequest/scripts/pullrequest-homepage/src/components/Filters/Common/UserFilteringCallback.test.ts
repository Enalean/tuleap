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
import { UserStub } from "../../../../tests/stubs/UserStub";
import { UserFilteringCallback } from "./UserFilteringCallback";

const jolasti = {
    is_disabled: false,
    value: UserStub.withIdAndName(101, "Joe l'asticot (jolasti)"),
};
const jdoe = { is_disabled: false, value: UserStub.withIdAndName(102, "John Doe (jdoe)") };
const hobo_joe = { is_disabled: true, value: UserStub.withIdAndName(103, "Joe the hobo (jhobo)") };

describe("UserFilteringCallback", () => {
    it("Given an empty query and a collection of LazyboxItems, then it should return all the items", () => {
        const filtered_users = UserFilteringCallback("", [jolasti, jdoe, hobo_joe]);

        expect(filtered_users).toHaveLength(3);
        expect(filtered_users).toStrictEqual([jolasti, jdoe, hobo_joe]);
    });

    it("Given a query and a collection of LazyboxItems, then it should only return items corresponding to the query", () => {
        const filtered_users = UserFilteringCallback("joe", [jolasti, jdoe, hobo_joe]);

        expect(filtered_users).toHaveLength(2);
        expect(filtered_users).toStrictEqual([jolasti, hobo_joe]);
    });
});

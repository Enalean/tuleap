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
import { GettextStub } from "../../../../tests/stubs/GettextStub";
import { ReviewerFilterBuilder } from "./ReviewerFilter";

const user_id = 102;
const user_display_name = "John Doe (jdoe)";

describe("ReviewerFilter", () => {
    it("Given a user, then it should return a ReviewerFilter", () => {
        const builder = ReviewerFilterBuilder(GettextStub);
        const reviewer = UserStub.withIdAndName(user_id, user_display_name);
        const filter = builder.fromReviewer(reviewer);

        expect(filter.id).toBe(user_id);
        expect(filter.type).toBe("reviewer");
        expect(filter.label).toBe(`Reviewer: ${user_display_name}`);
        expect(filter.value).toBe(reviewer);
        expect(filter.is_unique).toBe(true);
    });
});

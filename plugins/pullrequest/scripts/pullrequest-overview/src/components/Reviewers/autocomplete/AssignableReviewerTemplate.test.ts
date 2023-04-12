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
import type { User } from "@tuleap/plugin-pullrequest-rest-api-types";
import { getAssignableReviewer, getSelectedReviewers } from "./AssignableReviewerTemplate";

const user = {
    id: 102,
    display_name: "Joe l'Asticot (jolasti)",
} as User;

describe("AssignableReviewerTemplate", () => {
    describe("getAssignableReviewer", () => {
        it("should return null if the provided value is not a user", () => {
            expect(getAssignableReviewer({ foo: "bar" })).toBeNull();
        });

        it("should return the value if it is a user", () => {
            expect(getAssignableReviewer(user)).toStrictEqual(user);
        });
    });

    describe("getSelectedReviewers", () => {
        it("should return an empty array when the provided parameter is not an array", () => {
            expect(getSelectedReviewers("abcd")).toStrictEqual([]);
        });

        it("should return an array of users", () => {
            expect(getSelectedReviewers([user])).toStrictEqual([user]);
        });
    });
});

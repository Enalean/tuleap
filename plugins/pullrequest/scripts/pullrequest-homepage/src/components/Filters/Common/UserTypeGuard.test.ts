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
import { isUser } from "./UserTypeGuard";

describe("UserTypeGuard", () => {
    it("should return false when the given item is not an object", () => {
        expect(isUser(12)).toBe(false);
    });

    it("should return false when the given item is null", () => {
        expect(isUser(null)).toBe(false);
    });

    it("should return false when the given item is an object which does not match the User shape", () => {
        expect(
            isUser({
                id: 5,
                label: "Something",
                is_outline: false,
                color: "waffle-blue",
            }),
        ).toBe(false);
    });

    it("should return true when the given item is an object matching the User shape", () => {
        expect(isUser(UserStub.withIdAndName(102, "John Doe"))).toBe(true);
    });
});

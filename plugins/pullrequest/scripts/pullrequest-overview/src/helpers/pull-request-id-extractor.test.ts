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
import { extractPullRequestIdFromRouteParams } from "./pull-request-id-extractor";

describe("pull-request-id-extractor", () => {
    it("should return 0 when there is no id parameter", () => {
        expect(extractPullRequestIdFromRouteParams({ foo: "bar" })).toBe(0);
    });

    it("should return 0 when id is an array of strings", () => {
        expect(extractPullRequestIdFromRouteParams({ id: ["t", "e", "n"] })).toBe(0);
    });

    it("should return 0 when id is a string which does not contain a valid number", () => {
        expect(extractPullRequestIdFromRouteParams({ id: "ten" })).toBe(0);
    });

    it("should return the id as a number when it is valid", () => {
        expect(extractPullRequestIdFromRouteParams({ id: "10" })).toBe(10);
    });
});

/*
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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
import { isPullRequestFile } from "./is-pull-request-file";
import { FILE_STATUS_MODIFIED } from "../../api/rest-querier";

describe("is-pull-request-file", () => {
    it("should return false when the value is not an object", () => {
        expect(isPullRequestFile(1)).toBe(false);
    });

    it("should return false when the value is null", () => {});
    expect(isPullRequestFile(null)).toBe(false);

    it("should return false when the value is not a pull-request file", () => {
        expect(isPullRequestFile({ foo: "bar" })).toBe(false);
    });

    it("should return true when the value is a pull-request file", () => {
        expect(
            isPullRequestFile({
                path: "README.md",
                status: FILE_STATUS_MODIFIED,
                lines_added: "10",
                lines_removed: "0",
            }),
        ).toBe(true);
    });
});

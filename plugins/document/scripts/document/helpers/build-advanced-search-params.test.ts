/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { buildAdvancedSearchParams } from "./build-advanced-search-params";
import type { AdvancedSearchParams } from "../type";

describe("build-advanced-search-params", () => {
    it("should return an empty search params by default", () => {
        const expected: AdvancedSearchParams = {
            query: "",
            type: "",
            title: "",
            description: "",
        };
        expect(buildAdvancedSearchParams()).toStrictEqual(expected);
    });

    it("should accept partial params", () => {
        const expected: AdvancedSearchParams = {
            query: "",
            type: "folder",
            title: "lorem",
            description: "",
        };
        expect(buildAdvancedSearchParams({ title: "lorem", type: "folder" })).toStrictEqual(
            expected
        );
    });
});

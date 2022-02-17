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

import type { AdvancedSearchParams } from "../type";
import { isQueryEmpty } from "./is-query-empty";
import { buildAdvancedSearchParams } from "./build-advanced-search-params";

describe("isQueryEmpty", () => {
    it("should return true if no parameters have been given to the query", () => {
        // We don't use the helper buildAdvancedSearchParams() on purpose:
        // That way, there is a better chance that contributor that is adding
        // a new query parameter do not forget to update isQueryEmpty().
        // It is not bullet proof but we hope that forcing them to touch this
        // test file will help.
        const query_params: AdvancedSearchParams = {
            global_search: "",
            type: "",
            title: "",
            description: "",
            owner: "",
            create_date: null,
            update_date: null,
            obsolescence_date: null,
            status: "",
        };
        expect(isQueryEmpty(query_params)).toBe(true);
    });

    it.each<ReadonlyArray<Partial<AdvancedSearchParams>>>([
        [{ global_search: "lorem" }],
        [{ type: "folder" }],
        [{ title: "ipsum" }],
        [{ description: "doloret" }],
        [{ owner: "jdoe" }],
        [{ create_date: { date: "2022-01-30", operator: "<" } }],
        [{ update_date: { date: "2022-01-30", operator: "<" } }],
        [{ obsolescence_date: { date: "2022-01-30", operator: "<" } }],
        [{ status: "draft" }],
        [{ field_2: "lorem" }],
        [{ field_2: { date: "2022-01-30", operator: ">" } }],
    ])("should return false if parameter is filled with %s", (query_params) => {
        expect(isQueryEmpty(buildAdvancedSearchParams(query_params))).toBe(false);
    });
});

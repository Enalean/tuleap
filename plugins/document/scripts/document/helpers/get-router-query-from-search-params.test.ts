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

import { getRouterQueryFromSearchParams } from "./get-router-query-from-search-params";
import { buildAdvancedSearchParams } from "./build-advanced-search-params";
import type { AdvancedSearchParams } from "../type";
import type { Dictionary } from "vue-router/types/router";

describe("getRouterQueryFromSearchParams", () => {
    it("should omit empty params to not clutter the query url", () => {
        // We don't use the helper buildAdvancedSearchParams() on purpose:
        // That way, there is a better chance that contributor that is adding
        // a new query parameter do not forget to update isQueryEmpty().
        // It is not bullet proof but we hope that forcing them to touch this
        // test file will help.
        const query_params: AdvancedSearchParams = {
            global_search: "",
            id: "",
            type: "",
            filename: "",
            title: "",
            description: "",
            owner: "",
            create_date: null,
            update_date: null,
            obsolescence_date: null,
            status: "",
            sort: null,
        };
        expect(getRouterQueryFromSearchParams(query_params)).toStrictEqual({});
    });

    it.each<[Partial<AdvancedSearchParams>, Dictionary<string>]>([
        [{ global_search: "lorem" }, { q: "lorem" }],
        [{ type: "folder" }, { type: "folder" }],
        [{ id: "123" }, { id: "123" }],
        [{ filename: "bob.jpg" }, { filename: "bob.jpg" }],
        [
            { global_search: "lorem", type: "folder" },
            { q: "lorem", type: "folder" },
        ],
        [{ title: "lorem" }, { title: "lorem" }],
        [{ description: "lorem" }, { description: "lorem" }],
        [{ owner: "lorem" }, { owner: "lorem" }],
        [
            { create_date: { date: "2022-01-30", operator: "<" } },
            { create_date: "2022-01-30", create_date_op: "<" },
        ],
        [{ create_date: { date: "", operator: "<" } }, {}],
        [
            { update_date: { date: "2022-01-30", operator: "<" } },
            { update_date: "2022-01-30", update_date_op: "<" },
        ],
        [{ update_date: { date: "", operator: "<" } }, {}],
        [
            { obsolescence_date: { date: "2022-01-30", operator: "<" } },
            { obsolescence_date: "2022-01-30", obsolescence_date_op: "<" },
        ],
        [{ obsolescence_date: { date: "", operator: "<" } }, {}],
        [{ status: "draft" }, { status: "draft" }],
        [
            { field_2: { date: "2022-01-30", operator: "<" } },
            { field_2: "2022-01-30", field_2_op: "<" },
        ],
        [{ field_2: { date: "", operator: "<" } }, {}],
    ])(
        "should return the url parameters based from search parameters (%s, %s)",
        (params, expected) => {
            expect(getRouterQueryFromSearchParams(buildAdvancedSearchParams(params))).toStrictEqual(
                expected,
            );
        },
    );
});

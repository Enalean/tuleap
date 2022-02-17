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

import { getRestBodyFromSearchParams } from "./get-rest-body-from-search-params";
import { buildAdvancedSearchParams } from "./build-advanced-search-params";
import type { AdvancedSearchParams, SearchDate } from "../type";

describe("get-rest-body-from-search-params", () => {
    it("should return nothing for empty params", () => {
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
        expect(getRestBodyFromSearchParams(query_params)).toStrictEqual({});
    });

    it.each<
        [
            Partial<AdvancedSearchParams>,
            Record<
                string,
                | string
                | Record<string, string>
                | ReadonlyArray<{
                      name: string;
                      value?: string;
                      value_date?: SearchDate;
                  }>
            >
        ]
    >([
        [{}, {}],
        [{ global_search: "lorem" }, { global_search: "lorem" }],
        [{ type: "folder" }, { type: "folder" }],
        [
            { global_search: "lorem", type: "folder" },
            { global_search: "lorem", type: "folder" },
        ],
        [{ title: "lorem" }, { title: "lorem" }],
        [{ description: "lorem" }, { description: "lorem" }],
        [{ owner: "lorem" }, { owner: "lorem" }],
        [
            { create_date: { date: "2022-01-30", operator: "<" } },
            { create_date: { date: "2022-01-30", operator: "<" } },
        ],
        [{ create_date: { date: "", operator: "<" } }, {}],
        [
            { update_date: { date: "2022-01-30", operator: "<" } },
            { update_date: { date: "2022-01-30", operator: "<" } },
        ],
        [{ update_date: { date: "", operator: "<" } }, {}],
        [
            { obsolescence_date: { date: "2022-01-30", operator: "<" } },
            { obsolescence_date: { date: "2022-01-30", operator: "<" } },
        ],
        [{ obsolescence_date: { date: "", operator: "<" } }, {}],
        [{ status: "draft" }, { status: "draft" }],
        [{ field_2: "lorem" }, { custom_properties: [{ name: "field_2", value: "lorem" }] }],
        [
            { field_2: { date: "2022-01-30", operator: "<" } },
            {
                custom_properties: [
                    {
                        name: "field_2",
                        value_date: { date: "2022-01-30", operator: "<" },
                    },
                ],
            },
        ],
        [{ field_2: { date: "", operator: "<" } }, {}],
    ])("should return the body based on search parameters (%s, %s)", (params, expected) => {
        expect(getRestBodyFromSearchParams(buildAdvancedSearchParams(params))).toStrictEqual(
            expected
        );
    });
});

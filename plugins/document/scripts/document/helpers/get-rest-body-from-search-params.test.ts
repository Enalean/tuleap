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
import type { AdvancedSearchParams, SearchBodyRest } from "../type";

describe("get-rest-body-from-search-params", () => {
    it("should return nothing for empty params", () => {
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
        expect(getRestBodyFromSearchParams(query_params)).toStrictEqual({
            sort: [{ name: "update_date", order: "desc" }],
        });
    });

    it.each<[Partial<AdvancedSearchParams>, SearchBodyRest]>([
        [{}, { sort: [{ name: "update_date", order: "desc" }] }],
        [
            { global_search: "lorem" },
            { global_search: "lorem", sort: [{ name: "update_date", order: "desc" }] },
        ],
        [
            { id: "123" },
            {
                properties: [{ name: "id", value: "123" }],
                sort: [{ name: "update_date", order: "desc" }],
            },
        ],
        [{ id: "" }, { sort: [{ name: "update_date", order: "desc" }] }],
        [
            { filename: "bob.jpg" },
            {
                properties: [{ name: "filename", value: "bob.jpg" }],
                sort: [{ name: "update_date", order: "desc" }],
            },
        ],
        [{ filename: "" }, { sort: [{ name: "update_date", order: "desc" }] }],
        [
            { type: "folder" },
            {
                properties: [{ name: "type", value: "folder" }],
                sort: [{ name: "update_date", order: "desc" }],
            },
        ],
        [
            { global_search: "lorem", type: "folder" },
            {
                global_search: "lorem",
                properties: [{ name: "type", value: "folder" }],
                sort: [{ name: "update_date", order: "desc" }],
            },
        ],
        [
            { title: "lorem" },
            {
                properties: [{ name: "title", value: "lorem" }],
                sort: [{ name: "update_date", order: "desc" }],
            },
        ],
        [
            { description: "lorem" },
            {
                properties: [{ name: "description", value: "lorem" }],
                sort: [{ name: "update_date", order: "desc" }],
            },
        ],
        [
            { owner: "lorem" },
            {
                properties: [{ name: "owner", value: "lorem" }],
                sort: [{ name: "update_date", order: "desc" }],
            },
        ],
        [
            { create_date: { date: "2022-01-30", operator: "<" } },
            {
                properties: [
                    { name: "create_date", value_date: { date: "2022-01-30", operator: "<" } },
                ],
                sort: [{ name: "update_date", order: "desc" }],
            },
        ],
        [
            { create_date: { date: "", operator: "<" } },
            { sort: [{ name: "update_date", order: "desc" }] },
        ],
        [
            { update_date: { date: "2022-01-30", operator: "<" } },
            {
                properties: [
                    { name: "update_date", value_date: { date: "2022-01-30", operator: "<" } },
                ],
                sort: [{ name: "update_date", order: "desc" }],
            },
        ],
        [
            { update_date: { date: "", operator: "<" } },
            { sort: [{ name: "update_date", order: "desc" }] },
        ],
        [
            { obsolescence_date: { date: "2022-01-30", operator: "<" } },
            {
                properties: [
                    {
                        name: "obsolescence_date",
                        value_date: { date: "2022-01-30", operator: "<" },
                    },
                ],
                sort: [{ name: "update_date", order: "desc" }],
            },
        ],
        [
            { obsolescence_date: { date: "", operator: "<" } },
            { sort: [{ name: "update_date", order: "desc" }] },
        ],
        [
            { status: "draft" },
            {
                properties: [{ name: "status", value: "draft" }],
                sort: [{ name: "update_date", order: "desc" }],
            },
        ],
        [
            { field_2: "lorem" },
            {
                properties: [{ name: "field_2", value: "lorem" }],
                sort: [{ name: "update_date", order: "desc" }],
            },
        ],
        [
            { field_2: { date: "2022-01-30", operator: "<" } },
            {
                properties: [
                    {
                        name: "field_2",
                        value_date: { date: "2022-01-30", operator: "<" },
                    },
                ],
                sort: [{ name: "update_date", order: "desc" }],
            },
        ],
        [
            { field_2: { date: "", operator: "<" } },
            { sort: [{ name: "update_date", order: "desc" }] },
        ],
    ])("should return the body based on search parameters (%s, %s)", (params, expected) => {
        expect(getRestBodyFromSearchParams(buildAdvancedSearchParams(params))).toStrictEqual(
            expected
        );
    });
});

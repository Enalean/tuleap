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

import { getSearchPropsFromRoute } from "./get-search-props-from-route";
import type { Route } from "vue-router/types/router";
import { buildAdvancedSearchParams } from "../helpers/build-advanced-search-params";

describe("get-search-props-from-route", () => {
    describe("folder_id", () => {
        it("should search in the provided folder_id", () => {
            const { folder_id } = getSearchPropsFromRoute(
                { params: { folder_id: 102 }, query: {} } as unknown as Route,
                101
            );
            expect(folder_id).toBe(102);
        });

        it("should consider that the search is performed in root folder if folder_id is not provided", () => {
            const { folder_id } = getSearchPropsFromRoute(
                { params: {}, query: {} } as unknown as Route,
                101
            );
            expect(folder_id).toBe(101);
        });
    });

    describe("offset", () => {
        it("should search at the provided offset", () => {
            const { offset } = getSearchPropsFromRoute(
                { params: {}, query: { offset: 50 } } as unknown as Route,
                101
            );
            expect(offset).toBe(50);
        });

        it("should default to first offset", () => {
            const { offset } = getSearchPropsFromRoute(
                { params: {}, query: {} } as unknown as Route,
                101
            );
            expect(offset).toBe(0);
        });
    });

    describe("query", () => {
        it("should default to empty query", () => {
            const { query } = getSearchPropsFromRoute(
                { params: {}, query: {} } as unknown as Route,
                101
            );
            expect(query).toStrictEqual(buildAdvancedSearchParams());
        });

        it("should accept q parameter", () => {
            const { query } = getSearchPropsFromRoute(
                { params: {}, query: { q: "Lorem ipsum" } } as unknown as Route,
                101
            );
            expect(query).toStrictEqual(
                buildAdvancedSearchParams({ global_search: "Lorem ipsum" })
            );
        });

        it("should accept type parameter", () => {
            const { query } = getSearchPropsFromRoute(
                { params: {}, query: { type: "wiki" } } as unknown as Route,
                101
            );
            expect(query).toStrictEqual(buildAdvancedSearchParams({ type: "wiki" }));
        });

        it("should default to no type if user starts to update the url parameter by hand", () => {
            const { query } = getSearchPropsFromRoute(
                { params: {}, query: { type: "unknown" } } as unknown as Route,
                101
            );
            expect(query).toStrictEqual(buildAdvancedSearchParams({ type: "" }));
        });

        it("should accept title parameter", () => {
            const { query } = getSearchPropsFromRoute(
                { params: {}, query: { title: "Lorem ipsum" } } as unknown as Route,
                101
            );
            expect(query).toStrictEqual(buildAdvancedSearchParams({ title: "Lorem ipsum" }));
        });

        it("should accept description parameter", () => {
            const { query } = getSearchPropsFromRoute(
                { params: {}, query: { description: "Lorem ipsum" } } as unknown as Route,
                101
            );
            expect(query).toStrictEqual(buildAdvancedSearchParams({ description: "Lorem ipsum" }));
        });

        it("should accept owner parameter", () => {
            const { query } = getSearchPropsFromRoute(
                { params: {}, query: { owner: "jdoe" } } as unknown as Route,
                101
            );
            expect(query).toStrictEqual(buildAdvancedSearchParams({ owner: "jdoe" }));
        });

        it.each([["<"], ["="], [">"]])(
            "should accept create_date parameter with %s operator",
            (operator) => {
                const { query } = getSearchPropsFromRoute(
                    {
                        params: {},
                        query: { create_date: "2022-01-30", create_date_op: operator },
                    } as unknown as Route,
                    101
                );
                expect(query).toStrictEqual(
                    buildAdvancedSearchParams({ create_date: { operator, date: "2022-01-30" } })
                );
            }
        );

        it.each([["<"], ["="], [">"]])(
            "should accept update_date parameter with %s operator",
            (operator) => {
                const { query } = getSearchPropsFromRoute(
                    {
                        params: {},
                        query: { update_date: "2022-01-30", update_date_op: operator },
                    } as unknown as Route,
                    101
                );
                expect(query).toStrictEqual(
                    buildAdvancedSearchParams({ update_date: { operator, date: "2022-01-30" } })
                );
            }
        );

        it.each([["<"], ["="], [">"]])(
            "should accept obsolescence_date parameter with %s operator",
            (operator) => {
                const { query } = getSearchPropsFromRoute(
                    {
                        params: {},
                        query: { obsolescence_date: "2022-01-30", obsolescence_date_op: operator },
                    } as unknown as Route,
                    101
                );
                expect(query).toStrictEqual(
                    buildAdvancedSearchParams({
                        obsolescence_date: { operator, date: "2022-01-30" },
                    })
                );
            }
        );
    });
});

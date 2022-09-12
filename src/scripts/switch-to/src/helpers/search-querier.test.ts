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

import * as fetch_result from "@tuleap/fetch-result";
import { query } from "./search-querier";
import type { ResultAsync } from "neverthrow";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { ItemDefinition } from "../type";
import type { OptionsWithAutoEncodedParameters } from "@tuleap/fetch-result/src/ResultFetcher";

describe("search-querier", () => {
    const url = "/search";
    const keywords = "keywords";
    const PAGINATION_SIZE_HEADER = "X-PAGINATION-SIZE";
    const PAGINATION_LIMIT_HEADER = "X-PAGINATION-LIMIT";
    const PAGINATION_LIMIT_MAX_HEADER = "X-PAGINATION-LIMIT-MAX";

    describe("query", () => {
        it("should propagate the API error", async () => {
            const post_spy = jest.spyOn(fetch_result, "post");
            post_spy.mockReturnValue(errAsync(Fault.fromMessage("Something went wrong")));

            const result = await query(url, keywords);
            expect(result.isErr()).toBe(true);
        });

        it("should return the results", async () => {
            const post_spy = jest.spyOn(fetch_result, "post");
            post_spy.mockReturnValue(
                okAsync({
                    headers: {
                        get: (name: string): string | null =>
                            name === PAGINATION_SIZE_HEADER
                                ? "2"
                                : name === PAGINATION_LIMIT_MAX_HEADER
                                ? "50"
                                : name === PAGINATION_LIMIT_HEADER
                                ? "50"
                                : null,
                    },
                    json: () =>
                        Promise.resolve([
                            { title: "toto", html_url: "/toto" },
                            { title: "titi", html_url: "/titi" },
                        ] as ItemDefinition[]),
                } as unknown as Response)
            );

            const result = await query(url, keywords);
            expect(result.unwrapOr({})).toStrictEqual({
                results: {
                    "/toto": { title: "toto", html_url: "/toto" },
                    "/titi": { title: "titi", html_url: "/titi" },
                },
                has_more_results: false,
            });
        });

        it("should deduplicate the results", async () => {
            const post_spy = jest.spyOn(fetch_result, "post");
            post_spy.mockReturnValue(
                okAsync({
                    headers: {
                        get: (name: string): string | null =>
                            name === PAGINATION_SIZE_HEADER
                                ? "2"
                                : name === PAGINATION_LIMIT_MAX_HEADER
                                ? "50"
                                : name === PAGINATION_LIMIT_HEADER
                                ? "50"
                                : null,
                    },
                    json: () =>
                        Promise.resolve([
                            { title: "toto", html_url: "/toto" },
                            { title: "toto", html_url: "/toto" },
                        ] as ItemDefinition[]),
                } as unknown as Response)
            );

            const result = await query(url, keywords);
            expect(result.unwrapOr({})).toStrictEqual({
                results: {
                    "/toto": { title: "toto", html_url: "/toto" },
                },
                has_more_results: false,
            });
        });

        it("should query another pages to have 15 results and indicate if there are more results", async () => {
            const post_spy = jest.spyOn(fetch_result, "post");
            post_spy.mockImplementation(
                (
                    url: string,
                    options: OptionsWithAutoEncodedParameters
                ): ResultAsync<Response, Fault> => {
                    const results_by_offset = new Map<number, ItemDefinition[]>([
                        [
                            0,
                            [
                                { title: "toto-01", html_url: "/toto-01" },
                                { title: "toto-02", html_url: "/toto-02" },
                            ] as ItemDefinition[],
                        ],
                        [
                            50,
                            [
                                { title: "toto-03", html_url: "/toto-03" },
                                { title: "toto-04", html_url: "/toto-04" },
                                { title: "toto-05", html_url: "/toto-05" },
                                { title: "toto-06", html_url: "/toto-06" },
                                { title: "toto-07", html_url: "/toto-07" },
                                { title: "toto-08", html_url: "/toto-08" },
                                { title: "toto-09", html_url: "/toto-09" },
                                { title: "toto-10", html_url: "/toto-10" },
                                { title: "toto-11", html_url: "/toto-11" },
                            ] as ItemDefinition[],
                        ],
                        [100, [] as ItemDefinition[]],
                        [
                            150,
                            [
                                { title: "toto-12", html_url: "/toto-12" },
                                { title: "toto-13", html_url: "/toto-13" },
                                { title: "toto-14", html_url: "/toto-14" },
                                { title: "toto-15", html_url: "/toto-15" },
                                { title: "toto-16", html_url: "/toto-16" },
                                { title: "toto-17", html_url: "/toto-17" },
                                { title: "toto-18", html_url: "/toto-18" },
                            ] as ItemDefinition[],
                        ],
                    ]);

                    const offset = Number(options.params ? options.params.offset : 0);
                    const results = results_by_offset.get(offset) || ([] as ItemDefinition[]);

                    return okAsync({
                        headers: {
                            get: (name: string): string | null =>
                                name === PAGINATION_SIZE_HEADER
                                    ? "2000"
                                    : name === PAGINATION_LIMIT_MAX_HEADER
                                    ? "50"
                                    : name === PAGINATION_LIMIT_HEADER
                                    ? "50"
                                    : null,
                        },
                        json: () => Promise.resolve(results),
                    } as unknown as Response);
                }
            );

            const result = await query(url, keywords);
            expect(result.unwrapOr({})).toStrictEqual({
                results: {
                    "/toto-01": { title: "toto-01", html_url: "/toto-01" },
                    "/toto-02": { title: "toto-02", html_url: "/toto-02" },
                    "/toto-03": { title: "toto-03", html_url: "/toto-03" },
                    "/toto-04": { title: "toto-04", html_url: "/toto-04" },
                    "/toto-05": { title: "toto-05", html_url: "/toto-05" },
                    "/toto-06": { title: "toto-06", html_url: "/toto-06" },
                    "/toto-07": { title: "toto-07", html_url: "/toto-07" },
                    "/toto-08": { title: "toto-08", html_url: "/toto-08" },
                    "/toto-09": { title: "toto-09", html_url: "/toto-09" },
                    "/toto-10": { title: "toto-10", html_url: "/toto-10" },
                    "/toto-11": { title: "toto-11", html_url: "/toto-11" },
                    "/toto-12": { title: "toto-12", html_url: "/toto-12" },
                    "/toto-13": { title: "toto-13", html_url: "/toto-13" },
                    "/toto-14": { title: "toto-14", html_url: "/toto-14" },
                    "/toto-15": { title: "toto-15", html_url: "/toto-15" },
                },
                has_more_results: true,
            });
        });

        it("should deduplicate results accross pages", async () => {
            const post_spy = jest.spyOn(fetch_result, "post");
            post_spy.mockImplementation(
                (
                    url: string,
                    options: OptionsWithAutoEncodedParameters
                ): ResultAsync<Response, Fault> => {
                    const results_by_offset = new Map<number, ItemDefinition[]>([
                        [
                            0,
                            [
                                { title: "toto-01", html_url: "/toto-01" },
                                { title: "toto-02", html_url: "/toto-02" },
                            ] as ItemDefinition[],
                        ],
                        [
                            50,
                            [
                                { title: "toto-01", html_url: "/toto-01" },
                                { title: "toto-03", html_url: "/toto-03" },
                            ] as ItemDefinition[],
                        ],
                        [100, [] as ItemDefinition[]],
                        [
                            150,
                            [
                                { title: "toto-01", html_url: "/toto-01" },
                                { title: "toto-03", html_url: "/toto-03" },
                                { title: "toto-04", html_url: "/toto-04" },
                            ] as ItemDefinition[],
                        ],
                    ]);

                    const offset = Number(options.params ? options.params.offset : 0);
                    const results = results_by_offset.get(offset) || ([] as ItemDefinition[]);

                    return okAsync({
                        headers: {
                            get: (name: string): string | null =>
                                name === PAGINATION_SIZE_HEADER
                                    ? "2000"
                                    : name === PAGINATION_LIMIT_MAX_HEADER
                                    ? "50"
                                    : name === PAGINATION_LIMIT_HEADER
                                    ? "50"
                                    : null,
                        },
                        json: () => Promise.resolve(results),
                    } as unknown as Response);
                }
            );

            const result = await query(url, keywords);
            expect(result.unwrapOr({})).toStrictEqual({
                results: {
                    "/toto-01": { title: "toto-01", html_url: "/toto-01" },
                    "/toto-02": { title: "toto-02", html_url: "/toto-02" },
                    "/toto-03": { title: "toto-03", html_url: "/toto-03" },
                    "/toto-04": { title: "toto-04", html_url: "/toto-04" },
                },
                has_more_results: false,
            });
        });
    });
});

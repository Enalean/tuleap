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
import * as delayed_querier from "../helpers/delayed-querier";
import { createPinia, setActivePinia } from "pinia";
import { useFullTextStore } from "./fulltext";
import { FULLTEXT_MINIMUM_LENGTH_FOR_QUERY } from "./type";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { ItemDefinition } from "../type";

describe("FullText Store", () => {
    let cancelPendingQuery: jest.Mock;
    let scheduleQuery: jest.Mock;
    let callback_promise: Promise<void> | undefined;

    beforeEach(() => {
        setActivePinia(createPinia());
        cancelPendingQuery = jest.fn();
        scheduleQuery = jest.fn((callback: () => Promise<void>) => {
            callback_promise = callback();
        });

        jest.spyOn(delayed_querier, "delayedQuerier").mockImplementation(() => ({
            cancelPendingQuery,
            scheduleQuery,
        }));
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    describe("search", () => {
        it.each([FULLTEXT_MINIMUM_LENGTH_FOR_QUERY])(
            `should have empty results and previous query canceled if keywords length is less than %s characters`,
            (min) => {
                const store = useFullTextStore();
                store.$patch({
                    fulltext_search_url: "/search",
                    fulltext_search_is_available: true,
                    fulltext_search_results: {
                        "/toto": { title: "toto", html_url: "/toto" } as ItemDefinition,
                        "/titi": { title: "titi", html_url: "/titi" } as ItemDefinition,
                    },
                });

                store.search("a".repeat(min - 1));
                expect(cancelPendingQuery).toHaveBeenCalled();
                expect(scheduleQuery).not.toHaveBeenCalled();

                expect(store.fulltext_search_is_loading).toBe(false);
                expect(store.fulltext_search_is_error).toBe(false);
                expect(store.fulltext_search_results).toStrictEqual({});
            }
        );

        it("should indicate that search results are loading", () => {
            const store = useFullTextStore();
            store.$patch({
                fulltext_search_url: "/search",
                fulltext_search_is_available: true,
                fulltext_search_results: {},
            });
            const post_spy = jest.spyOn(fetch_result, "postJSON");
            post_spy.mockReturnValue(
                okAsync({
                    json: () => Promise.resolve([]),
                } as unknown as Response)
            );

            store.search("foobar");
            expect(scheduleQuery).toHaveBeenCalled();

            expect(store.fulltext_search_is_loading).toBe(true);
            expect(store.fulltext_search_is_error).toBe(false);
            expect(store.fulltext_search_results).toStrictEqual({});
        });

        it("should indicate that search is in error", async () => {
            const store = useFullTextStore();
            store.$patch({
                fulltext_search_url: "/search",
                fulltext_search_is_available: true,
                fulltext_search_results: {},
            });

            const post_spy = jest.spyOn(fetch_result, "postJSON");
            post_spy.mockReturnValue(errAsync(Fault.fromMessage("Something went wrong")));

            store.search("foobar");
            expect(scheduleQuery).toHaveBeenCalled();
            await callback_promise;

            expect(store.fulltext_search_is_loading).toBe(false);
            expect(store.fulltext_search_is_error).toBe(true);
            expect(store.fulltext_search_results).toStrictEqual({});
        });

        it("should mark fulltext search as disabled when 404", async () => {
            const store = useFullTextStore();
            store.$patch({
                fulltext_search_url: "/search",
                fulltext_search_is_available: true,
                fulltext_search_results: {},
            });

            const post_spy = jest.spyOn(fetch_result, "postJSON");
            post_spy.mockReturnValue(
                errAsync({
                    isNotFound: () => true,
                    ...Fault.fromMessage("Something went wrong"),
                })
            );

            store.search("foobar");
            expect(scheduleQuery).toHaveBeenCalled();
            await callback_promise;

            expect(store.fulltext_search_is_available).toBe(false);
            expect(store.fulltext_search_is_loading).toBe(false);
            expect(store.fulltext_search_is_error).toBe(false);
            expect(store.fulltext_search_results).toStrictEqual({});
        });

        it("should store the search results", async () => {
            const store = useFullTextStore();
            store.$patch({
                fulltext_search_url: "/search",
                fulltext_search_is_available: true,
                fulltext_search_results: {},
            });

            const post_spy = jest.spyOn(fetch_result, "postJSON");
            post_spy.mockReturnValue(
                okAsync({
                    json: () =>
                        Promise.resolve([
                            { title: "toto", html_url: "/toto" },
                            { title: "titi", html_url: "/titi" },
                        ] as ItemDefinition[]),
                } as unknown as Response)
            );

            store.search("foobar");
            expect(scheduleQuery).toHaveBeenCalled();
            await callback_promise;

            expect(store.fulltext_search_is_loading).toBe(false);
            expect(store.fulltext_search_is_error).toBe(false);
            expect(store.fulltext_search_results).toStrictEqual({
                "/toto": { title: "toto", html_url: "/toto" } as ItemDefinition,
                "/titi": { title: "titi", html_url: "/titi" } as ItemDefinition,
            });
        });

        it("should deduplicate results", async () => {
            const store = useFullTextStore();
            store.$patch({
                fulltext_search_url: "/search",
                fulltext_search_is_available: true,
                fulltext_search_results: {},
            });

            const post_spy = jest.spyOn(fetch_result, "postJSON");
            post_spy.mockReturnValue(
                okAsync({
                    json: () =>
                        Promise.resolve([
                            { title: "titi", html_url: "/titi" },
                            { title: "titi", html_url: "/titi" },
                        ] as ItemDefinition[]),
                } as unknown as Response)
            );

            store.search("foobar");
            expect(scheduleQuery).toHaveBeenCalled();
            await callback_promise;

            expect(store.fulltext_search_is_loading).toBe(false);
            expect(store.fulltext_search_is_error).toBe(false);
            expect(store.fulltext_search_results).toStrictEqual({
                "/titi": { title: "titi", html_url: "/titi" } as ItemDefinition,
            });
        });

        it("should not perform the search if fts is not available", async () => {
            const store = useFullTextStore();
            store.$patch({
                fulltext_search_url: "/search",
                fulltext_search_is_available: false,
                fulltext_search_results: {},
            });

            const post_spy = jest.spyOn(fetch_result, "postJSON");
            post_spy.mockReturnValue(
                okAsync({
                    json: () =>
                        Promise.resolve([{ title: "toto" }, { title: "titi" }] as ItemDefinition[]),
                } as unknown as Response)
            );

            store.search("foobar");
            expect(scheduleQuery).not.toHaveBeenCalled();
            await callback_promise;

            expect(post_spy).not.toHaveBeenCalled();
        });
    });
});

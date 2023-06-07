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

import * as search_querier from "../helpers/search-querier";
import * as delayed_querier from "../helpers/delayed-querier";
import { createPinia, setActivePinia } from "pinia";
import { useFullTextStore } from "./fulltext";
import { FULLTEXT_MINIMUM_LENGTH_FOR_QUERY } from "./type";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { ItemDefinition, Project, QuickLink } from "../type";
import { useRootStore } from "./root";
import type { StoppableQuery } from "../helpers/delayed-querier";
import { useKeyboardNavigationStore } from "./keyboard-navigation";
import { uri } from "@tuleap/fetch-result";

describe("FullText Store", () => {
    let cancelPendingQuery: jest.Mock;
    let scheduleQuery: jest.Mock;

    beforeEach(() => {
        setActivePinia(createPinia());
        cancelPendingQuery = jest.fn();
        scheduleQuery = jest.fn(({ run }: StoppableQuery): void => {
            run();
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
                    fulltext_search_url: uri`/search`,
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
                fulltext_search_url: uri`/search`,
                fulltext_search_is_available: true,
                fulltext_search_results: {},
            });

            const result = okAsync({ results: {}, has_more_results: false, next_offset: 0 });
            const query_spy = jest.spyOn(search_querier, "querier");
            query_spy.mockImplementation(
                (url, keywords, previously_fetched_results, onItemReceived, onComplete) => ({
                    run: (): void => onComplete(result),
                    stop: jest.fn(),
                })
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
                fulltext_search_url: uri`/search`,
                fulltext_search_is_available: true,
                fulltext_search_results: {},
            });

            const result = errAsync(Fault.fromMessage("Something went wrong"));
            const query_spy = jest.spyOn(search_querier, "querier");
            query_spy.mockImplementation(
                (url, keywords, previously_fetched_results, onItemReceived, onComplete) => ({
                    run: (): void => onComplete(result),
                    stop: jest.fn(),
                })
            );

            store.search("foobar");
            expect(scheduleQuery).toHaveBeenCalled();
            await result;

            expect(store.fulltext_search_is_loading).toBe(false);
            expect(store.fulltext_search_is_error).toBe(true);
            expect(store.fulltext_search_results).toStrictEqual({});
        });

        it("should mark fulltext search as disabled when 404", async () => {
            const store = useFullTextStore();
            store.$patch({
                fulltext_search_url: uri`/search`,
                fulltext_search_is_available: true,
                fulltext_search_results: {},
            });

            const result = errAsync({
                isNotFound: () => true,
                ...Fault.fromMessage("Something went wrong"),
            });
            const query_spy = jest.spyOn(search_querier, "querier");
            query_spy.mockImplementation(
                (url, keywords, previously_fetched_results, onItemReceived, onComplete) => ({
                    run: (): void => onComplete(result),
                    stop: jest.fn(),
                })
            );

            store.search("foobar");
            expect(scheduleQuery).toHaveBeenCalled();
            await result;

            expect(store.fulltext_search_is_available).toBe(false);
            expect(store.fulltext_search_is_loading).toBe(false);
            expect(store.fulltext_search_is_error).toBe(false);
            expect(store.fulltext_search_results).toStrictEqual({});
        });

        it("should store the search results", async () => {
            const store = useFullTextStore();
            store.$patch({
                fulltext_search_url: uri`/search`,
                fulltext_search_is_available: true,
                fulltext_search_results: {},
            });

            const result = okAsync({
                results: {
                    "/toto": { title: "toto", html_url: "/toto" } as ItemDefinition,
                    "/titi": { title: "titi", html_url: "/titi" } as ItemDefinition,
                },
                has_more_results: false,
                next_offset: 0,
            });
            const query_spy = jest.spyOn(search_querier, "querier");
            query_spy.mockImplementation(
                (url, keywords, previously_fetched_results, onItemReceived, onComplete) => ({
                    run: (): void => onComplete(result),
                    stop: jest.fn(),
                })
            );

            store.search("foobar");
            expect(scheduleQuery).toHaveBeenCalled();
            await result;

            expect(store.fulltext_search_is_loading).toBe(false);
            expect(store.fulltext_search_is_error).toBe(false);
            expect(store.fulltext_search_results).toStrictEqual({
                "/toto": { title: "toto", html_url: "/toto" } as ItemDefinition,
                "/titi": { title: "titi", html_url: "/titi" } as ItemDefinition,
            });
            expect(store.fulltext_search_has_more_results).toBe(false);
        });

        it("should incrementally add items to search results so that user has better chance to see progress if results are spanned between multiple pages", async () => {
            const store = useFullTextStore();
            store.$patch({
                fulltext_search_url: uri`/search`,
                fulltext_search_is_available: true,
                fulltext_search_results: {},
            });

            const result = okAsync({
                results: {
                    "/toto": { title: "toto", html_url: "/toto" } as ItemDefinition,
                    "/titi": { title: "titi", html_url: "/titi" } as ItemDefinition,
                },
                next_offset: 0,
                has_more_results: false,
            });
            const query_spy = jest.spyOn(search_querier, "querier");
            query_spy.mockImplementation(
                (url, keywords, previously_fetched_results, onItemReceived, onComplete) => ({
                    run: (): void => {
                        onItemReceived({ title: "toto", html_url: "/toto" } as ItemDefinition);
                        onItemReceived({ title: "titi", html_url: "/titi" } as ItemDefinition);
                        onComplete(result);
                    },
                    stop: jest.fn(),
                })
            );

            store.search("foobar");
            expect(scheduleQuery).toHaveBeenCalled();
            expect(store.fulltext_search_results).toStrictEqual({
                "/toto": { title: "toto", html_url: "/toto" } as ItemDefinition,
                "/titi": { title: "titi", html_url: "/titi" } as ItemDefinition,
            });
            expect(store.fulltext_search_is_loading).toBe(true);
            await result;
        });

        it("should store the fact that there are more results", async () => {
            const store = useFullTextStore();
            store.$patch({
                fulltext_search_url: uri`/search`,
                fulltext_search_is_available: true,
                fulltext_search_results: {},
            });

            const result = okAsync({
                results: {
                    "/toto": { title: "toto", html_url: "/toto" } as ItemDefinition,
                    "/titi": { title: "titi", html_url: "/titi" } as ItemDefinition,
                },
                has_more_results: true,
                next_offset: 0,
            });
            const query_spy = jest.spyOn(search_querier, "querier");
            query_spy.mockImplementation(
                (url, keywords, previously_fetched_results, onItemReceived, onComplete) => ({
                    run: (): void => onComplete(result),
                    stop: jest.fn(),
                })
            );

            store.search("foobar");
            expect(scheduleQuery).toHaveBeenCalled();
            await result;

            expect(store.fulltext_search_is_loading).toBe(false);
            expect(store.fulltext_search_is_error).toBe(false);
            expect(store.fulltext_search_results).toStrictEqual({
                "/toto": { title: "toto", html_url: "/toto" } as ItemDefinition,
                "/titi": { title: "titi", html_url: "/titi" } as ItemDefinition,
            });
            expect(store.fulltext_search_has_more_results).toBe(true);
        });

        it("should not perform the search if fts is not available", () => {
            const store = useFullTextStore();
            store.$patch({
                fulltext_search_url: uri`/search`,
                fulltext_search_is_available: false,
                fulltext_search_results: {},
            });

            store.search("foobar");
            expect(scheduleQuery).not.toHaveBeenCalled();
        });
    });

    describe("more", () => {
        it("should not perform the search if fts is not available", () => {
            const store = useFullTextStore();
            store.$patch({
                fulltext_search_url: uri`/search`,
                fulltext_search_is_available: false,
                fulltext_search_results: {},
            });

            store.more();
            expect(scheduleQuery).not.toHaveBeenCalled();
        });

        it("should store more results", async () => {
            const root_store = useRootStore();
            root_store.$patch({
                filter_value: "keyword",
            });

            const store = useFullTextStore();
            store.$patch({
                fulltext_search_url: uri`/search`,
                fulltext_search_is_available: true,
                fulltext_search_results: {
                    "/toto": { title: "toto", html_url: "/toto" } as ItemDefinition,
                    "/titi": { title: "titi", html_url: "/titi" } as ItemDefinition,
                },
                fulltext_search_has_more_results: true,
                fulltext_search_next_offset: 15,
            });

            const result = okAsync({
                results: {
                    "/toto": { title: "toto", html_url: "/toto" } as ItemDefinition,
                    "/titi": { title: "titi", html_url: "/titi" } as ItemDefinition,
                    "/tata": { title: "tata", html_url: "/tata" } as ItemDefinition,
                    "/tutu": { title: "tutu", html_url: "/tutu" } as ItemDefinition,
                },
                has_more_results: false,
                next_offset: 20,
            });
            const query_spy = jest.spyOn(search_querier, "querier");
            let received_previously_fetched_results;
            query_spy.mockImplementation(
                (url, keywords, previously_fetched_results, onItemReceived, onComplete) => {
                    received_previously_fetched_results = previously_fetched_results;
                    return {
                        run: (): void => onComplete(result),
                        stop: jest.fn(),
                    };
                }
            );

            store.more();
            expect(scheduleQuery).toHaveBeenCalled();
            expect(received_previously_fetched_results).toStrictEqual({
                has_more_results: true,
                next_offset: 15,
                results: {
                    "/toto": { title: "toto", html_url: "/toto" } as ItemDefinition,
                    "/titi": { title: "titi", html_url: "/titi" } as ItemDefinition,
                },
            });

            await result;

            expect(store.fulltext_search_is_loading).toBe(false);
            expect(store.fulltext_search_is_error).toBe(false);
            expect(store.fulltext_search_results).toStrictEqual({
                "/toto": { title: "toto", html_url: "/toto" } as ItemDefinition,
                "/titi": { title: "titi", html_url: "/titi" } as ItemDefinition,
                "/tata": { title: "tata", html_url: "/tata" } as ItemDefinition,
                "/tutu": { title: "tutu", html_url: "/tutu" } as ItemDefinition,
            });
            expect(store.fulltext_search_has_more_results).toBe(false);
        });
    });

    describe("changeFocusFromSearchResult", () => {
        describe("When user hits ArrowRight", () => {
            it("does nothing if item has no quick link", () => {
                const first_search_result = {
                    title: "first search result",
                    html_url: "/first-search-result",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                } as ItemDefinition;

                const fts = useFullTextStore();
                fts.$patch({
                    fulltext_search_url: uri`/search`,
                    fulltext_search_is_available: true,
                    fulltext_search_results: {
                        "/first-search-result": first_search_result,
                    },
                });

                const navigation_store = useKeyboardNavigationStore();
                navigation_store.$patch({
                    programmatically_focused_element: first_search_result,
                });

                fts.changeFocusFromSearchResult({
                    entry: first_search_result,
                    key: "ArrowRight",
                });

                expect(navigation_store.programmatically_focused_element).toStrictEqual(
                    first_search_result
                );
            });

            it("should focus on first quick link", () => {
                const quick_link = { html_url: "/nous-c-est-le-gout" } as QuickLink;
                const first_search_result = {
                    title: "first search result",
                    html_url: "/first-search-result",
                    quick_links: [quick_link] as ReadonlyArray<QuickLink>,
                } as ItemDefinition;

                const fts = useFullTextStore();
                fts.$patch({
                    fulltext_search_url: uri`/search`,
                    fulltext_search_is_available: true,
                    fulltext_search_results: {
                        "/first-search-result": first_search_result,
                    },
                });

                const navigation_store = useKeyboardNavigationStore();
                navigation_store.$patch({
                    programmatically_focused_element: first_search_result,
                });

                fts.changeFocusFromSearchResult({
                    entry: first_search_result,
                    key: "ArrowRight",
                });

                expect(navigation_store.programmatically_focused_element).toStrictEqual(quick_link);
            });
        });

        describe("When user hits ArrowUp", () => {
            it("goes up", () => {
                const first_search_result = {
                    title: "first search result",
                    html_url: "/first-search-result",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                } as ItemDefinition;
                const second_search_result = {
                    title: "second search result",
                    html_url: "/second-search-result",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                } as ItemDefinition;

                const fts = useFullTextStore();
                fts.$patch({
                    fulltext_search_url: uri`/search`,
                    fulltext_search_is_available: true,
                    fulltext_search_results: {
                        "/first-search-result": first_search_result,
                        "/second-search-result": second_search_result,
                    },
                });

                const navigation_store = useKeyboardNavigationStore();
                navigation_store.$patch({
                    programmatically_focused_element: second_search_result,
                });

                fts.changeFocusFromSearchResult({
                    entry: second_search_result,
                    key: "ArrowUp",
                });

                expect(navigation_store.programmatically_focused_element).toStrictEqual(
                    first_search_result
                );
            });

            it("should focus the last recent item if the first search result has already the focus", () => {
                const first_project = {
                    project_uri: "/first",
                    project_name: "First lorem",
                } as Project;
                const another_project = {
                    project_uri: "/another",
                    project_name: "Another lorem",
                } as Project;

                const first_entry = {
                    html_url: "/first-entry",
                    title: "a lorem",
                } as ItemDefinition;
                const another_entry = {
                    html_url: "/another-entry",
                    title: "b lorem",
                } as ItemDefinition;

                const first_search_result = {
                    title: "first search result",
                    html_url: "/first-search-result",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                } as ItemDefinition;
                const second_search_result = {
                    title: "second search result",
                    html_url: "/second-search-result",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                } as ItemDefinition;

                const fts = useFullTextStore();
                fts.$patch({
                    fulltext_search_url: uri`/search`,
                    fulltext_search_is_available: true,
                    fulltext_search_results: {
                        "/first-search-result": first_search_result,
                        "/second-search-result": second_search_result,
                    },
                });

                const store = useRootStore();
                store.$patch({
                    history: {
                        entries: [first_entry, another_entry],
                    },
                    projects: [first_project, another_project],
                    filter_value: "lorem",
                });
                const navigation_store = useKeyboardNavigationStore();
                navigation_store.$patch({
                    programmatically_focused_element: first_search_result,
                });

                fts.changeFocusFromSearchResult({
                    entry: first_search_result,
                    key: "ArrowUp",
                });

                expect(navigation_store.programmatically_focused_element).toStrictEqual(
                    another_entry
                );
            });

            it("should focus the last project if the first search result has already the focus and there is no recent items", () => {
                const first_project = {
                    project_uri: "/first",
                    project_name: "First lorem",
                } as Project;
                const another_project = {
                    project_uri: "/another",
                    project_name: "Another lorem",
                } as Project;

                const first_search_result = {
                    title: "first search result",
                    html_url: "/first-search-result",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                } as ItemDefinition;
                const second_search_result = {
                    title: "second search result",
                    html_url: "/second-search-result",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                } as ItemDefinition;

                const fts = useFullTextStore();
                fts.$patch({
                    fulltext_search_url: uri`/search`,
                    fulltext_search_is_available: true,
                    fulltext_search_results: {
                        "/first-search-result": first_search_result,
                        "/second-search-result": second_search_result,
                    },
                });

                const store = useRootStore();
                store.$patch({
                    history: {
                        entries: [],
                    },
                    projects: [first_project, another_project],
                    filter_value: "lorem",
                });
                const navigation_store = useKeyboardNavigationStore();
                navigation_store.$patch({
                    programmatically_focused_element: first_search_result,
                });

                fts.changeFocusFromSearchResult({
                    entry: first_search_result,
                    key: "ArrowUp",
                });

                expect(navigation_store.programmatically_focused_element).toStrictEqual(
                    another_project
                );
            });

            it("should focus the filter input if the first search result has already the focus and there is no recent items and there is no projects", () => {
                const first_search_result = {
                    title: "first search result",
                    html_url: "/first-search-result",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                } as ItemDefinition;
                const second_search_result = {
                    title: "second search result",
                    html_url: "/second-search-result",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                } as ItemDefinition;

                const fts = useFullTextStore();
                fts.$patch({
                    fulltext_search_url: uri`/search`,
                    fulltext_search_is_available: true,
                    fulltext_search_results: {
                        "/first-search-result": first_search_result,
                        "/second-search-result": second_search_result,
                    },
                });

                const store = useRootStore();
                store.$patch({
                    history: {
                        entries: [],
                    },
                    projects: [],
                    filter_value: "lorem",
                });
                const navigation_store = useKeyboardNavigationStore();
                navigation_store.$patch({
                    programmatically_focused_element: first_search_result,
                });

                fts.changeFocusFromSearchResult({
                    entry: first_search_result,
                    key: "ArrowUp",
                });

                expect(navigation_store.programmatically_focused_element).toBeNull();
            });
        });

        describe("When user hits ArrowDown", () => {
            it("goes down", () => {
                const first_search_result = {
                    title: "first search result",
                    html_url: "/first-search-result",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                } as ItemDefinition;
                const second_search_result = {
                    title: "second search result",
                    html_url: "/second-search-result",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                } as ItemDefinition;

                const fts = useFullTextStore();
                fts.$patch({
                    fulltext_search_url: uri`/search`,
                    fulltext_search_is_available: true,
                    fulltext_search_results: {
                        "/first-search-result": first_search_result,
                        "/second-search-result": second_search_result,
                    },
                });

                const navigation_store = useKeyboardNavigationStore();
                navigation_store.$patch({
                    programmatically_focused_element: first_search_result,
                });

                fts.changeFocusFromSearchResult({
                    entry: first_search_result,
                    key: "ArrowDown",
                });

                expect(navigation_store.programmatically_focused_element).toStrictEqual(
                    second_search_result
                );
            });

            it("does nothing if the last recent item has already the focus", () => {
                const first_search_result = {
                    title: "first search result",
                    html_url: "/first-search-result",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                } as ItemDefinition;
                const second_search_result = {
                    title: "second search result",
                    html_url: "/second-search-result",
                    quick_links: [] as ReadonlyArray<QuickLink>,
                } as ItemDefinition;

                const fts = useFullTextStore();
                fts.$patch({
                    fulltext_search_url: uri`/search`,
                    fulltext_search_is_available: true,
                    fulltext_search_results: {
                        "/first-search-result": first_search_result,
                        "/second-search-result": second_search_result,
                    },
                });

                const navigation_store = useKeyboardNavigationStore();
                navigation_store.$patch({
                    programmatically_focused_element: second_search_result,
                });

                fts.changeFocusFromSearchResult({
                    entry: second_search_result,
                    key: "ArrowDown",
                });

                expect(navigation_store.programmatically_focused_element).toStrictEqual(
                    second_search_result
                );
            });
        });
    });

    describe("focusFirstSearchResult", () => {
        it("should focus the first search result", () => {
            const first_search_result = {
                title: "first search result",
                html_url: "/first-search-result",
                quick_links: [] as ReadonlyArray<QuickLink>,
            } as ItemDefinition;
            const second_search_result = {
                title: "second search result",
                html_url: "/second-search-result",
                quick_links: [] as ReadonlyArray<QuickLink>,
            } as ItemDefinition;

            const fts = useFullTextStore();
            fts.$patch({
                fulltext_search_url: uri`/search`,
                fulltext_search_is_available: true,
                fulltext_search_results: {
                    "/first-search-result": first_search_result,
                    "/second-search-result": second_search_result,
                },
            });

            const navigation_store = useKeyboardNavigationStore();
            navigation_store.$patch({
                programmatically_focused_element: { project_name: "acme" } as Project,
            });

            fts.focusFirstSearchResult();

            expect(navigation_store.programmatically_focused_element).toStrictEqual(
                first_search_result
            );
        });
    });
});

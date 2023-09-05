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

import { shallowMount } from "@vue/test-utils";
import SearchResults from "./SearchResults.vue";
import SearchResultsError from "./SearchResultsError.vue";
import SearchResultsEmpty from "./SearchResultsEmpty.vue";
import SearchResultsList from "./SearchResultsList.vue";
import SearchQueryTooSmall from "./SearchQueryTooSmall.vue";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import type { Project, UserHistory, ItemDefinition } from "../../../../type";
import type { FullTextState } from "../../../../stores/type";
import { FULLTEXT_MINIMUM_LENGTH_FOR_QUERY } from "../../../../stores/type";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import { uri } from "@tuleap/fetch-result";

describe("SearchResults", () => {
    describe("FullText search available", () => {
        it("should display a loading state and potential existing results", () => {
            const wrapper = shallowMount(SearchResults, {
                global: getGlobalTestOptions(
                    createTestingPinia({
                        initialState: {
                            root: {
                                filter_value: "foobar",
                            },
                            fulltext: {
                                fulltext_search_url: uri`/api/search`,
                                fulltext_search_is_available: true,
                                fulltext_search_results: {},
                                fulltext_search_is_loading: true,
                                fulltext_search_is_error: false,
                            } as FullTextState,
                        },
                    }),
                ),
            });

            expect(wrapper.attributes("aria-busy")).toBe("true");
            expect(wrapper.find("[data-test=switch-to-search-results-loading]").exists()).toBe(
                true,
            );
            expect(wrapper.findComponent(SearchResultsError).exists()).toBe(false);
            expect(wrapper.findComponent(SearchResultsEmpty).exists()).toBe(false);
            expect(wrapper.findComponent(SearchResultsList).exists()).toBe(true);
        });

        it("should display an error state", () => {
            const wrapper = shallowMount(SearchResults, {
                global: getGlobalTestOptions(
                    createTestingPinia({
                        initialState: {
                            root: {
                                filter_value: "foobar",
                            },
                            fulltext: {
                                fulltext_search_url: uri`/api/search`,
                                fulltext_search_is_available: true,
                                fulltext_search_results: {},
                                fulltext_search_is_loading: false,
                                fulltext_search_is_error: true,
                            } as FullTextState,
                        },
                    }),
                ),
            });

            expect(wrapper.attributes("aria-busy")).toBe("false");
            expect(wrapper.find("[data-test=switch-to-search-results-loading]").exists()).toBe(
                false,
            );
            expect(wrapper.findComponent(SearchResultsError).exists()).toBe(true);
            expect(wrapper.findComponent(SearchResultsEmpty).exists()).toBe(false);
            expect(wrapper.findComponent(SearchResultsList).exists()).toBe(false);
        });

        it("should display search results", () => {
            const wrapper = shallowMount(SearchResults, {
                global: getGlobalTestOptions(
                    createTestingPinia({
                        initialState: {
                            root: {
                                filter_value: "foobar",
                            },
                            fulltext: {
                                fulltext_search_url: uri`/api/search`,
                                fulltext_search_is_available: true,
                                fulltext_search_results: {
                                    "/toto": { title: "toto" } as ItemDefinition,
                                },
                                fulltext_search_is_loading: false,
                                fulltext_search_is_error: false,
                                fulltext_search_has_more_results: false,
                            } as FullTextState,
                        },
                    }),
                ),
            });

            expect(wrapper.attributes("aria-busy")).toBe("false");
            expect(wrapper.find("[data-test=switch-to-search-results-loading]").exists()).toBe(
                false,
            );
            expect(wrapper.findComponent(SearchResultsError).exists()).toBe(false);
            expect(wrapper.findComponent(SearchResultsEmpty).exists()).toBe(false);
            expect(wrapper.findComponent(SearchResultsList).exists()).toBe(true);
        });

        it.each([
            [[] as Project[], [] as ItemDefinition[]],
            [[] as Project[], [{}] as ItemDefinition[]],
            [[{}] as Project[], [] as ItemDefinition[]],
            [[{}] as Project[], [{}] as ItemDefinition[]],
        ])(
            `Given there is no search results
            And there is matching projects %s or recent items %s
            Then it should always display an empty state for this section.`,
            (filtered_projects, filtered_history_entries) => {
                const useSwitchToStore = defineStore("root", {
                    getters: {
                        filtered_history: (): UserHistory => ({
                            entries: filtered_history_entries,
                        }),
                        filtered_projects: (): Project[] => filtered_projects,
                        keywords: (): string => "foobar",
                    },
                });

                const pinia = createTestingPinia({
                    initialState: {
                        fulltext: {
                            fulltext_search_url: uri`/api/search`,
                            fulltext_search_is_available: true,
                            fulltext_search_results: {},
                            fulltext_search_is_loading: false,
                            fulltext_search_is_error: false,
                        } as FullTextState,
                    },
                });
                useSwitchToStore(pinia);

                const wrapper = shallowMount(SearchResults, {
                    global: getGlobalTestOptions(pinia),
                });
                expect(wrapper.attributes("aria-busy")).toBe("false");
                expect(wrapper.findComponent(SearchResultsError).exists()).toBe(false);
                expect(wrapper.findComponent(SearchResultsEmpty).exists()).toBe(true);
                expect(wrapper.findComponent(SearchResultsList).exists()).toBe(false);
            },
        );

        it.each([
            [[] as Project[], [{}] as ItemDefinition[]],
            [[{}] as Project[], [] as ItemDefinition[]],
            [[{}] as Project[], [{}] as ItemDefinition[]],
        ])(
            `Given the search query is less than ${FULLTEXT_MINIMUM_LENGTH_FOR_QUERY} chars
            And there is matching projects %s or recent items %s
            Then it should not display something.`,
            (filtered_projects, filtered_history_entries) => {
                const useSwitchToStore = defineStore("root", {
                    getters: {
                        filtered_history: (): UserHistory => ({
                            entries: filtered_history_entries,
                        }),
                        filtered_projects: (): Project[] => filtered_projects,
                        keywords: (): string => "a".repeat(FULLTEXT_MINIMUM_LENGTH_FOR_QUERY - 1),
                    },
                });

                const pinia = createTestingPinia({
                    initialState: {
                        fulltext: {
                            fulltext_search_url: uri`/api/search`,
                            fulltext_search_is_available: true,
                            fulltext_search_results: {},
                            fulltext_search_is_loading: false,
                            fulltext_search_is_error: false,
                        } as FullTextState,
                    },
                });
                useSwitchToStore(pinia);

                const wrapper = shallowMount(SearchResults, {
                    global: getGlobalTestOptions(pinia),
                });

                expect(wrapper.element).toMatchInlineSnapshot(`<!--v-if-->`);
            },
        );

        it(`Given the search query is less than ${FULLTEXT_MINIMUM_LENGTH_FOR_QUERY} chars
            And there is no matching projects and recent items
            Then it should ask to user to enter more than ${FULLTEXT_MINIMUM_LENGTH_FOR_QUERY} chars.`, () => {
            const useSwitchToStore = defineStore("root", {
                getters: {
                    filtered_history: (): UserHistory => ({
                        entries: [],
                    }),
                    filtered_projects: (): Project[] => [],
                    keywords: (): string => "a".repeat(FULLTEXT_MINIMUM_LENGTH_FOR_QUERY - 1),
                },
            });

            const pinia = createTestingPinia({
                initialState: {
                    fulltext: {
                        fulltext_search_url: uri`/api/search`,
                        fulltext_search_is_available: true,
                        fulltext_search_results: {},
                        fulltext_search_is_loading: false,
                        fulltext_search_is_error: false,
                    } as FullTextState,
                },
            });
            useSwitchToStore(pinia);

            const wrapper = shallowMount(SearchResults, {
                global: getGlobalTestOptions(pinia),
            });

            expect(wrapper.findComponent(SearchQueryTooSmall).exists()).toBe(true);
        });
    });

    describe("FullText search is NOT available", () => {
        let fulltext_state: FullTextState;
        beforeEach(() => {
            fulltext_state = {
                fulltext_search_url: uri`/api/search`,
                fulltext_search_is_available: false,
                fulltext_search_results: {},
                fulltext_search_is_loading: false,
                fulltext_search_is_error: false,
                fulltext_search_has_more_results: false,
            };
        });

        it.each(["a".repeat(FULLTEXT_MINIMUM_LENGTH_FOR_QUERY - 1), "foobar"])(
            `should display No results
            when user search for %s
            and the list of filtered projects and the list of filtered recent items are empty`,
            (filter_value) => {
                const useSwitchToStore = defineStore("root", {
                    getters: {
                        filtered_history: (): UserHistory => ({ entries: [] }),
                        filtered_projects: (): Project[] => [],
                        keywords: (): string => filter_value,
                    },
                });

                const pinia = createTestingPinia({
                    initialState: {
                        fulltext: fulltext_state,
                    },
                });
                useSwitchToStore(pinia);

                const wrapper = shallowMount(SearchResults, {
                    global: getGlobalTestOptions(pinia),
                });

                expect(wrapper.attributes("aria-busy")).toBe("false");
                expect(wrapper.findComponent(SearchResultsError).exists()).toBe(false);
                expect(wrapper.findComponent(SearchResultsEmpty).exists()).toBe(true);
            },
        );

        it.each([
            [[] as Project[], [{}] as ItemDefinition[]],
            [[{}] as Project[], [] as ItemDefinition[]],
            [[{}] as Project[], [{}] as ItemDefinition[]],
        ])(
            `should not display anything
            when there is at least one matching project %s or recent item %s
            because FTS is not enabled and we don't want to display a "No results" which may confuse people.`,
            (filtered_projects, filtered_history_entries) => {
                const useSwitchToStore = defineStore("root", {
                    getters: {
                        filtered_history: (): UserHistory => ({
                            entries: filtered_history_entries,
                        }),
                        filtered_projects: (): Project[] => filtered_projects,
                        keywords: (): string => "foobar",
                    },
                });

                const pinia = createTestingPinia({
                    initialState: {
                        fulltext: fulltext_state,
                    },
                });
                useSwitchToStore(pinia);

                const wrapper = shallowMount(SearchResults, {
                    global: getGlobalTestOptions(pinia),
                });
                expect(wrapper.element).toMatchInlineSnapshot(`<!--v-if-->`);
            },
        );
    });
});

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

import { describe, expect, it } from "@jest/globals";
import { shallowMount } from "@vue/test-utils";
import SearchResultsList from "./SearchResultsList.vue";
import ItemEntry from "../ItemEntry.vue";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import { createTestingPinia } from "@pinia/testing";
import type { ItemDefinition } from "../../../../type";
import type { FullTextState } from "../../../../stores/type";
import { useFullTextStore } from "../../../../stores/fulltext";
import { uri } from "@tuleap/fetch-result";

describe("SearchResultsList", () => {
    const default_state: FullTextState = {
        fulltext_search_url: uri`/api/search`,
        fulltext_search_is_available: true,
        fulltext_search_results: {},
        fulltext_search_is_loading: false,
        fulltext_search_is_error: false,
        fulltext_search_has_more_results: false,
    };

    it("should display a list of items", () => {
        const wrapper = shallowMount(SearchResultsList, {
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        fulltext: {
                            ...default_state,
                            fulltext_search_results: {
                                "/toto": { title: "toto" } as ItemDefinition,
                                "/titi": { title: "titi" } as ItemDefinition,
                            },
                        } as FullTextState,
                    },
                }),
            ),
        });

        expect(wrapper.findAllComponents(ItemEntry)).toHaveLength(2);
        expect(wrapper.find("[data-test=more-button]").exists()).toBe(false);
        expect(wrapper.find("[data-test=more-busy]").exists()).toBe(false);
    });

    it("should display a more button if there is more results", () => {
        const wrapper = shallowMount(SearchResultsList, {
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        fulltext: {
                            ...default_state,
                            fulltext_search_has_more_results: true,
                        } as FullTextState,
                    },
                }),
            ),
        });

        expect(wrapper.find("[data-test=more-button]").exists()).toBe(true);
        expect(wrapper.find("[data-test=more-busy]").exists()).toBe(false);
    });

    it("should display a spinner if there is more results and a search is occurring", () => {
        const wrapper = shallowMount(SearchResultsList, {
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        fulltext: {
                            ...default_state,
                            fulltext_search_has_more_results: true,
                            fulltext_search_is_loading: true,
                        } as FullTextState,
                    },
                }),
            ),
        });

        expect(wrapper.find("[data-test=more-button]").exists()).toBe(true);
        expect(wrapper.find("[data-test=more-busy]").exists()).toBe(true);
    });

    it("should ask to load more results", async () => {
        const wrapper = shallowMount(SearchResultsList, {
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        fulltext: {
                            ...default_state,
                            fulltext_search_has_more_results: true,
                        } as FullTextState,
                    },
                }),
            ),
        });

        const button = wrapper.find("[data-test=more-button]");

        await button.trigger("click");

        expect(useFullTextStore().more).toHaveBeenCalled();
    });
});

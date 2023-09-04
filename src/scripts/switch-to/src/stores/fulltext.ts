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

import { defineStore } from "pinia";
import type { FullTextState, FocusFromItemPayload } from "./type";
import { FULLTEXT_MINIMUM_LENGTH_FOR_QUERY } from "./type";
import { ref } from "vue";
import { delayedQuerier } from "../helpers/delayed-querier";
import type { Fault } from "@tuleap/fault";
import { useRootStore } from "./root";
import type { QueryResults } from "../helpers/search-querier";
import { querier } from "../helpers/search-querier";
import type { ItemDefinition } from "../type";
import type { ResultAsync } from "neverthrow";
import { useKeyboardNavigationStore } from "./keyboard-navigation";
import type { EncodedURI } from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";

export const useFullTextStore = defineStore("fulltext", () => {
    const fulltext_search_url = ref<FullTextState["fulltext_search_url"]>(uri`/api/v1/search`);
    const fulltext_search_results = ref<FullTextState["fulltext_search_results"]>({});
    const fulltext_search_is_error = ref<FullTextState["fulltext_search_is_error"]>(false);
    const fulltext_search_is_loading = ref<FullTextState["fulltext_search_is_loading"]>(false);
    const fulltext_search_is_available = ref<FullTextState["fulltext_search_is_available"]>(true);
    const fulltext_search_has_more_results =
        ref<FullTextState["fulltext_search_has_more_results"]>(false);
    const fulltext_search_next_offset = ref<number>(0);

    const delayed_querier = delayedQuerier();

    function search(keywords: string): void {
        if (fulltext_search_is_available.value === false) {
            return;
        }

        fulltext_search_results.value = {};
        fulltext_search_has_more_results.value = false;
        fulltext_search_next_offset.value = 0;

        applySearch(keywords);
    }

    function more(): void {
        if (fulltext_search_is_available.value === false) {
            return;
        }

        applySearch(useRootStore().keywords);
    }

    function applySearch(keywords: string): void {
        if (fulltext_search_is_available.value === false) {
            return;
        }

        const url: EncodedURI = fulltext_search_url.value;

        fulltext_search_is_loading.value = true;
        fulltext_search_is_error.value = false;

        delayed_querier.cancelPendingQuery();

        if (keywords.length < FULLTEXT_MINIMUM_LENGTH_FOR_QUERY) {
            fulltext_search_is_loading.value = false;
            return;
        }

        const previously_fetched_results: QueryResults = {
            results: fulltext_search_results.value,
            has_more_results: fulltext_search_has_more_results.value,
            next_offset: fulltext_search_next_offset.value,
        };
        delayed_querier.scheduleQuery(
            querier(
                url,
                keywords,
                previously_fetched_results,
                (result: ItemDefinition): void => {
                    if (typeof fulltext_search_results.value[result.html_url] === "undefined") {
                        fulltext_search_results.value[result.html_url] = result;
                    }
                },
                (results: ResultAsync<QueryResults, Fault>): void => {
                    results.match(
                        ({ results, has_more_results, next_offset }): void => {
                            fulltext_search_results.value = results;
                            fulltext_search_is_loading.value = false;
                            fulltext_search_has_more_results.value = has_more_results;
                            fulltext_search_next_offset.value = next_offset;
                        },
                        (fault: Fault) => {
                            fulltext_search_is_loading.value = false;
                            if ("isNotFound" in fault && fault.isNotFound() === true) {
                                fulltext_search_is_available.value = false;
                                return;
                            }
                            fulltext_search_is_error.value = true;
                        },
                    );
                },
            ),
        );
    }

    function focusFirstSearchResult(): void {
        const result_keys = Object.keys(fulltext_search_results.value);
        if (result_keys.length > 0) {
            useKeyboardNavigationStore().setProgrammaticallyFocusedElement(
                fulltext_search_results.value[result_keys[0]],
            );
        }
    }

    function changeFocusFromSearchResult(payload: FocusFromItemPayload): void {
        const key = payload.key;
        if (key === "ArrowLeft") {
            return;
        }

        const navigation_store = useKeyboardNavigationStore();
        if (key === "ArrowRight") {
            if (payload.entry.quick_links.length > 0) {
                navigation_store.setProgrammaticallyFocusedElement(payload.entry.quick_links[0]);
            }
            return;
        }

        const root_store = useRootStore();
        const result_keys = Object.keys(fulltext_search_results.value);
        const current_index = result_keys.findIndex(
            (html_url: string) => html_url === payload.entry.html_url,
        );
        const is_first_result = current_index === 0;
        if (is_first_result && key === "ArrowUp") {
            if (root_store.filtered_history.entries.length > 0) {
                navigation_store.setProgrammaticallyFocusedElement(
                    root_store.filtered_history.entries[
                        root_store.filtered_history.entries.length - 1
                    ],
                );
            } else if (root_store.filtered_projects.length > 0) {
                navigation_store.setProgrammaticallyFocusedElement(
                    root_store.filtered_projects[root_store.filtered_projects.length - 1],
                );
            } else {
                navigation_store.setProgrammaticallyFocusedElement(null);
            }
            return;
        }

        navigateInsearchResults(current_index, key, result_keys);
    }

    function navigateInsearchResults(
        current_index: number,
        key: "ArrowUp" | "ArrowDown",
        result_keys: string[],
    ): void {
        if (current_index === -1) {
            return;
        }

        const focused_index = current_index + (key === "ArrowUp" ? -1 : 1);
        const is_out_of_boundaries =
            typeof fulltext_search_results.value[result_keys[focused_index]] === "undefined";
        if (is_out_of_boundaries) {
            return;
        }

        const navigation_store = useKeyboardNavigationStore();
        navigation_store.setProgrammaticallyFocusedElement(
            fulltext_search_results.value[result_keys[focused_index]],
        );
    }

    return {
        fulltext_search_url,
        fulltext_search_results,
        fulltext_search_is_error,
        fulltext_search_is_loading,
        fulltext_search_is_available,
        fulltext_search_has_more_results,
        fulltext_search_next_offset,
        search,
        more,
        changeFocusFromSearchResult,
        focusFirstSearchResult,
    };
});

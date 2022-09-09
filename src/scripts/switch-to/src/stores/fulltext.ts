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
import { postJSON } from "@tuleap/fetch-result";
import type { FullTextState, FocusFromItemPayload } from "./type";
import { FULLTEXT_MINIMUM_LENGTH_FOR_QUERY } from "./type";
import { ref } from "vue";
import { delayedQuerier } from "../helpers/delayed-querier";
import type { Fault } from "@tuleap/fault";
import type { ItemDefinition } from "../type";
import { useSwitchToStore } from "./index";

export const useFullTextStore = defineStore("fulltext", () => {
    const fulltext_search_url = ref<FullTextState["fulltext_search_url"]>("/api/v1/search");
    const fulltext_search_results = ref<FullTextState["fulltext_search_results"]>({});
    const fulltext_search_is_error = ref<FullTextState["fulltext_search_is_error"]>(false);
    const fulltext_search_is_loading = ref<FullTextState["fulltext_search_is_loading"]>(false);
    const fulltext_search_is_available = ref<FullTextState["fulltext_search_is_available"]>(true);

    const delayed_querier = delayedQuerier();

    function search(keywords: string): void {
        if (fulltext_search_is_available.value === false) {
            return;
        }

        const url: string = fulltext_search_url.value;

        fulltext_search_is_loading.value = true;
        fulltext_search_results.value = {};
        fulltext_search_is_error.value = false;

        delayed_querier.cancelPendingQuery();

        if (keywords.length < FULLTEXT_MINIMUM_LENGTH_FOR_QUERY) {
            fulltext_search_is_loading.value = false;
            return;
        }

        delayed_querier.scheduleQuery(() =>
            postJSON<ItemDefinition[]>(url, {
                search_query: {
                    keywords,
                },
            }).match(
                (results: ItemDefinition[]): void => {
                    fulltext_search_results.value = deduplicate(results);
                    fulltext_search_is_loading.value = false;
                },
                (fault: Fault) => {
                    fulltext_search_is_loading.value = false;
                    if ("isNotFound" in fault && fault.isNotFound() === true) {
                        fulltext_search_is_available.value = false;
                        return;
                    }
                    fulltext_search_is_error.value = true;
                }
            )
        );
    }

    function deduplicate(results: ItemDefinition[]): FullTextState["fulltext_search_results"] {
        return results.reduce(
            (
                deduplicated_entries: FullTextState["fulltext_search_results"],
                entry: ItemDefinition
            ): FullTextState["fulltext_search_results"] => {
                if (typeof deduplicated_entries[entry.html_url] === "undefined") {
                    deduplicated_entries[entry.html_url] = entry;
                }

                return deduplicated_entries;
            },
            {}
        );
    }

    function focusFirstSearchResult(): void {
        const result_keys = Object.keys(fulltext_search_results.value);
        if (result_keys.length > 0) {
            useSwitchToStore().setProgrammaticallyFocusedElement(
                fulltext_search_results.value[result_keys[0]]
            );
        }
    }

    function changeFocusFromSearchResult(payload: FocusFromItemPayload): void {
        const key = payload.key;
        if (key === "ArrowLeft") {
            return;
        }

        const root_store = useSwitchToStore();
        if (key === "ArrowRight") {
            if (payload.entry.quick_links.length > 0) {
                root_store.setProgrammaticallyFocusedElement(payload.entry.quick_links[0]);
            }
            return;
        }

        const result_keys = Object.keys(fulltext_search_results.value);
        const current_index = result_keys.findIndex(
            (html_url: string) => html_url === payload.entry.html_url
        );
        const is_first_result = current_index === 0;
        if (is_first_result && key === "ArrowUp") {
            if (root_store.filtered_history.entries.length > 0) {
                root_store.setProgrammaticallyFocusedElement(
                    root_store.filtered_history.entries[
                        root_store.filtered_history.entries.length - 1
                    ]
                );
            } else if (root_store.filtered_projects.length > 0) {
                root_store.setProgrammaticallyFocusedElement(
                    root_store.filtered_projects[root_store.filtered_projects.length - 1]
                );
            }
            return;
        }

        navigateInsearchResults(current_index, key, result_keys);
    }

    function navigateInsearchResults(
        current_index: number,
        key: "ArrowUp" | "ArrowDown",
        result_keys: string[]
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

        const root_store = useSwitchToStore();
        root_store.setProgrammaticallyFocusedElement(
            fulltext_search_results.value[result_keys[focused_index]]
        );
    }

    return {
        fulltext_search_url,
        fulltext_search_results,
        fulltext_search_is_error,
        fulltext_search_is_loading,
        fulltext_search_is_available,
        search,
        changeFocusFromSearchResult,
        focusFirstSearchResult,
    };
});

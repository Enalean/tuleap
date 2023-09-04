/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { State } from "./type";
import { defineStore } from "pinia";
import { get } from "@tuleap/tlp-fetch";
import type { Project, UserHistory, ItemDefinition } from "../type";
import { isMatchingFilterValue } from "../helpers/is-matching-filter-value";
import { useFullTextStore } from "./fulltext";
import { computed, ref } from "vue";

export const useRootStore = defineStore("root", () => {
    const projects = ref<State["projects"]>([]);
    const user_id = ref<State["user_id"]>(100);
    const is_loading_history = ref<State["is_loading_history"]>(false);
    const is_history_loaded = ref<State["is_history_loaded"]>(false);
    const is_history_in_error = ref<State["is_history_in_error"]>(false);
    const history = ref<State["history"]>({ entries: [] });
    const filter_value = ref<State["filter_value"]>("");

    const keywords = computed((): string => filter_value.value.trim());

    const filtered_history = computed(
        (): UserHistory => ({
            entries: history.value.entries.reduce(
                (matching_entries: ItemDefinition[], entry: ItemDefinition): ItemDefinition[] => {
                    if (isMatchingFilterValue(entry.title, keywords.value)) {
                        matching_entries.push(entry);
                    } else if (isMatchingFilterValue(entry.xref, keywords.value)) {
                        matching_entries.push(entry);
                    }

                    return matching_entries;
                },
                [],
            ),
        }),
    );

    const filtered_projects = computed((): Project[] =>
        projects.value.reduce((matching_projects: Project[], project: Project): Project[] => {
            if (isMatchingFilterValue(project.project_name, keywords.value)) {
                matching_projects.push(project);
            }

            return matching_projects;
        }, []),
    );

    const is_in_search_mode = computed((): boolean => keywords.value.length > 0);

    async function loadHistory(): Promise<void> {
        if (is_history_loaded.value) {
            return;
        }

        try {
            const response = await get(`/api/users/${user_id.value}/history`);
            const history: UserHistory = await response.json();
            saveHistory(history);
        } catch (e) {
            setErrorForHistory(true);
            throw e;
        }
    }

    function updateFilterValue(value: string): void {
        if (filter_value.value !== value) {
            filter_value.value = value;

            if (is_in_search_mode.value) {
                useFullTextStore().search(keywords.value);
            }
        }
    }

    function saveHistory(new_history: UserHistory): void {
        is_history_loaded.value = true;
        is_loading_history.value = false;
        history.value = new_history;
    }

    function setErrorForHistory(is_error: boolean): void {
        is_history_in_error.value = is_error;
    }

    return {
        projects,
        user_id,
        is_loading_history,
        is_history_loaded,
        is_history_in_error,
        history,
        filter_value,
        filtered_history,
        filtered_projects,
        keywords,
        is_in_search_mode,
        loadHistory,
        updateFilterValue,
    };
});

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

import type {
    FocusFromItemPayload,
    FocusFromProjectPayload,
    FocusFromQuickLinkPayload,
    KeyboardNavigationState,
} from "./type";
import { ref } from "vue";
import { defineStore } from "pinia";
import type { Project, ItemDefinition, QuickLink } from "../type";
import { useFullTextStore } from "./fulltext";
import { useRootStore } from "./root";

export const useKeyboardNavigationStore = defineStore("keyboard-navigation", () => {
    const programmatically_focused_element =
        ref<KeyboardNavigationState["programmatically_focused_element"]>(null);

    function changeFocusFromFilterInput(): void {
        const root_store = useRootStore();

        if (root_store.filtered_projects.length > 0) {
            programmatically_focused_element.value = root_store.filtered_projects[0];
        } else if (!focusFirstHistoryEntry()) {
            useFullTextStore().focusFirstSearchResult();
        }
    }

    function changeFocusFromQuickLink(payload: FocusFromQuickLinkPayload): void {
        const key = payload.key;

        if (key === "ArrowDown" || key === "ArrowUp") {
            if (payload.item) {
                changeFocusFromHistory({ key, entry: payload.item });
            } else if (payload.project) {
                changeFocusFromProject({ key, project: payload.project });
            }

            return;
        }

        if (key === "ArrowRight") {
            const quick_links = payload.project?.quick_links || payload.item?.quick_links || [];

            const next_index = quick_links.indexOf(payload.quick_link) + 1;
            if (!next_index) {
                return;
            }

            const root_store = useRootStore();
            if (next_index >= quick_links.length) {
                if (payload.project && root_store.keywords.length === 0) {
                    focusFirstHistoryEntry();
                }
                return;
            }

            programmatically_focused_element.value = quick_links[next_index];
            return;
        }

        if (key === "ArrowLeft") {
            const quick_links = payload.project?.quick_links || payload.item?.quick_links || [];

            const previous_index = quick_links.indexOf(payload.quick_link) - 1;
            if (previous_index < -1) {
                return;
            }

            if (previous_index === -1) {
                programmatically_focused_element.value = payload.project || payload.item;
                return;
            }

            programmatically_focused_element.value = quick_links[previous_index];
        }
    }

    function changeFocusFromProject(payload: FocusFromProjectPayload): void {
        if (payload.key === "ArrowLeft") {
            return;
        }

        const root_store = useRootStore();
        if (payload.key === "ArrowRight") {
            if (payload.project.quick_links.length > 0) {
                programmatically_focused_element.value = payload.project.quick_links[0];
                return;
            }

            if (root_store.keywords.length === 0) {
                focusFirstHistoryEntry();
            }

            return;
        }

        const current_index = root_store.filtered_projects.findIndex(
            (project: Project) => project.project_uri === payload.project.project_uri
        );
        const is_the_last_project = current_index === root_store.filtered_projects.length - 1;

        if (
            is_the_last_project &&
            payload.key === "ArrowDown" &&
            root_store.keywords.length !== 0
        ) {
            if (!focusFirstHistoryEntry()) {
                useFullTextStore().focusFirstSearchResult();
            }
            return;
        }

        const is_the_first_project = current_index === 0;
        if (
            (root_store.filtered_projects.length <= 1 || is_the_first_project) &&
            payload.key === "ArrowUp"
        ) {
            programmatically_focused_element.value = null;
            return;
        }

        navigateInCollection(root_store.filtered_projects, current_index, payload.key);
    }

    function focusFirstHistoryEntry(): boolean {
        const root_store = useRootStore();
        if (!root_store.is_history_loaded) {
            return false;
        }

        if (root_store.is_history_in_error) {
            return false;
        }

        if (root_store.filtered_history.entries.length === 0) {
            return false;
        }

        programmatically_focused_element.value = root_store.filtered_history.entries[0];
        return true;
    }

    function changeFocusFromHistory(payload: FocusFromItemPayload): void {
        if (payload.key === "ArrowRight") {
            if (payload.entry.quick_links.length > 0) {
                programmatically_focused_element.value = payload.entry.quick_links[0];
            }
            return;
        }

        const root_store = useRootStore();
        if (payload.key === "ArrowLeft") {
            if (root_store.keywords.length !== 0) {
                return;
            }

            if (root_store.filtered_projects.length === 0) {
                return;
            }

            programmatically_focused_element.value = root_store.filtered_projects[0];

            return;
        }

        const current_index = root_store.filtered_history.entries.findIndex(
            (entry: ItemDefinition) => entry.html_url === payload.entry.html_url
        );
        const is_the_first_entry = current_index === 0;
        if (is_the_first_entry && payload.key === "ArrowUp" && root_store.keywords.length !== 0) {
            if (root_store.filtered_projects.length !== 0) {
                programmatically_focused_element.value =
                    root_store.filtered_projects[root_store.filtered_projects.length - 1];
            } else {
                programmatically_focused_element.value = null;
            }
            return;
        }
        const is_the_last_entry = current_index === root_store.filtered_history.entries.length - 1;
        if (is_the_last_entry && payload.key === "ArrowDown" && root_store.keywords.length !== 0) {
            useFullTextStore().focusFirstSearchResult();
            return;
        }

        navigateInCollection(root_store.filtered_history.entries, current_index, payload.key);
    }

    function navigateInCollection(
        collection: ItemDefinition[] | Project[],
        current_index: number,
        key: "ArrowUp" | "ArrowDown"
    ): void {
        if (current_index === -1) {
            return;
        }

        const focused_index = current_index + (key === "ArrowUp" ? -1 : 1);
        const is_out_of_boundaries = typeof collection[focused_index] === "undefined";
        if (is_out_of_boundaries) {
            return;
        }

        programmatically_focused_element.value = collection[focused_index];
    }

    function setProgrammaticallyFocusedElement(
        element: Project | ItemDefinition | QuickLink | null
    ): void {
        programmatically_focused_element.value = element;
    }

    return {
        programmatically_focused_element,
        changeFocusFromQuickLink,
        changeFocusFromProject,
        focusFirstHistoryEntry,
        changeFocusFromHistory,
        setProgrammaticallyFocusedElement,
        changeFocusFromFilterInput,
    };
});

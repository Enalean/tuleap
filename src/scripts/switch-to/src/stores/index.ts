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
    State,
} from "./type";
import { defineStore } from "pinia";
import { get } from "@tuleap/tlp-fetch";
import type { Project, UserHistory, ItemDefinition } from "../type";
import { isMatchingFilterValue } from "../helpers/is-matching-filter-value";
import { useFullTextStore } from "./fulltext";

export const useSwitchToStore = defineStore("root", {
    state: (): State => ({
        projects: [],
        is_trove_cat_enabled: false,
        are_restricted_users_allowed: false,
        is_search_available: false,
        search_form: {
            type_of_search: "",
            hidden_fields: [],
        },
        user_id: 100,
        is_loading_history: false,
        is_history_loaded: false,
        is_history_in_error: false,
        history: { entries: [] },
        filter_value: "",
        programmatically_focused_element: null,
    }),
    getters: {
        filtered_history(): UserHistory {
            return {
                entries: this.history.entries.reduce(
                    (
                        matching_entries: ItemDefinition[],
                        entry: ItemDefinition
                    ): ItemDefinition[] => {
                        if (isMatchingFilterValue(entry.title, this.filter_value)) {
                            matching_entries.push(entry);
                        } else if (isMatchingFilterValue(entry.xref, this.filter_value)) {
                            matching_entries.push(entry);
                        }

                        return matching_entries;
                    },
                    []
                ),
            };
        },

        filtered_projects(): Project[] {
            return this.projects.reduce(
                (matching_projects: Project[], project: Project): Project[] => {
                    if (isMatchingFilterValue(project.project_name, this.filter_value)) {
                        matching_projects.push(project);
                    }

                    return matching_projects;
                },
                []
            );
        },
    },
    actions: {
        async loadHistory(): Promise<void> {
            if (this.is_history_loaded) {
                return;
            }

            try {
                const response = await get(`/api/users/${this.user_id}/history`);
                const history: UserHistory = await response.json();
                this.saveHistory(history);
            } catch (e) {
                this.setErrorForHistory(true);
                throw e;
            }
        },

        changeFocusFromQuickLink(payload: FocusFromQuickLinkPayload): void {
            const key = payload.key;

            if (key === "ArrowDown" || key === "ArrowUp") {
                if (payload.item) {
                    this.changeFocusFromHistory({ key, entry: payload.item });
                } else if (payload.project) {
                    this.changeFocusFromProject({ key, project: payload.project });
                }

                return;
            }

            if (key === "ArrowRight") {
                const quick_links = payload.project?.quick_links || payload.item?.quick_links || [];

                const next_index = quick_links.indexOf(payload.quick_link) + 1;
                if (!next_index) {
                    return;
                }

                if (next_index >= quick_links.length) {
                    if (payload.project && this.filter_value.length === 0) {
                        this.focusFirstHistoryEntry();
                    }
                    return;
                }

                this.programmatically_focused_element = quick_links[next_index];
                return;
            }

            if (key === "ArrowLeft") {
                const quick_links = payload.project?.quick_links || payload.item?.quick_links || [];

                const previous_index = quick_links.indexOf(payload.quick_link) - 1;
                if (previous_index < -1) {
                    return;
                }

                if (previous_index === -1) {
                    this.programmatically_focused_element = payload.project || payload.item;
                    return;
                }

                this.programmatically_focused_element = quick_links[previous_index];
            }
        },

        changeFocusFromProject(payload: FocusFromProjectPayload): void {
            if (payload.key === "ArrowLeft") {
                return;
            }

            if (payload.key === "ArrowRight") {
                if (payload.project.quick_links.length > 0) {
                    this.programmatically_focused_element = payload.project.quick_links[0];
                    return;
                }

                if (this.filter_value.length === 0) {
                    this.focusFirstHistoryEntry();
                }

                return;
            }

            const current_index = this.filtered_projects.findIndex(
                (project: Project) => project.project_uri === payload.project.project_uri
            );
            const is_the_last_project = current_index === this.filtered_projects.length - 1;

            if (
                is_the_last_project &&
                payload.key === "ArrowDown" &&
                this.filter_value.length !== 0
            ) {
                this.focusFirstHistoryEntry();
                return;
            }

            this.navigateInCollection(this.filtered_projects, current_index, payload.key);
        },

        focusFirstHistoryEntry(): void {
            if (!this.is_history_loaded) {
                return;
            }

            if (this.is_history_in_error) {
                return;
            }

            if (this.filtered_history.entries.length === 0) {
                return;
            }

            this.programmatically_focused_element = this.filtered_history.entries[0];
        },

        changeFocusFromHistory(payload: FocusFromItemPayload): void {
            if (payload.key === "ArrowRight") {
                if (payload.entry.quick_links.length > 0) {
                    this.programmatically_focused_element = payload.entry.quick_links[0];
                }
                return;
            }

            if (payload.key === "ArrowLeft") {
                if (this.filter_value.length !== 0) {
                    return;
                }

                if (this.filtered_projects.length === 0) {
                    return;
                }

                this.programmatically_focused_element = this.filtered_projects[0];

                return;
            }

            const current_index = this.filtered_history.entries.findIndex(
                (entry: ItemDefinition) => entry.html_url === payload.entry.html_url
            );
            const is_the_first_entry = current_index === 0;
            if (is_the_first_entry && payload.key === "ArrowUp" && this.filter_value.length !== 0) {
                if (this.filtered_projects.length !== 0) {
                    this.programmatically_focused_element =
                        this.filtered_projects[this.filtered_projects.length - 1];
                }
                return;
            }

            this.navigateInCollection(this.filtered_history.entries, current_index, payload.key);
        },

        navigateInCollection(
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

            this.programmatically_focused_element = collection[focused_index];
        },

        updateFilterValue(value: string): void {
            if (this.filter_value !== value) {
                this.filter_value = value;
                useFullTextStore().search(this.filter_value);
            }
        },

        saveHistory(history: UserHistory): void {
            this.is_history_loaded = true;
            this.is_loading_history = false;
            this.history = history;
        },

        setErrorForHistory(is_error: boolean): void {
            this.is_history_in_error = is_error;
        },
    },
});

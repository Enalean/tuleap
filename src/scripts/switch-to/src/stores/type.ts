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

import type { Project, SearchForm, UserHistory, ItemDefinition, QuickLink } from "../type";

export interface State {
    readonly projects: Project[];
    readonly is_trove_cat_enabled: boolean;
    readonly are_restricted_users_allowed: boolean;
    readonly is_search_available: boolean;
    readonly search_form: SearchForm;
    readonly user_id: number;
    is_loading_history: boolean;
    is_history_loaded: boolean;
    is_history_in_error: boolean;
    history: UserHistory;
    filter_value: string;
}

export interface FocusFromProjectPayload {
    readonly project: Project;
    readonly key: "ArrowUp" | "ArrowRight" | "ArrowDown" | "ArrowLeft";
}

export interface FocusFromItemPayload {
    readonly entry: ItemDefinition;
    readonly key: "ArrowUp" | "ArrowRight" | "ArrowDown" | "ArrowLeft";
}

export interface FocusFromQuickLinkPayload {
    readonly project: Project | null;
    readonly item: ItemDefinition | null;
    readonly quick_link: QuickLink;
    readonly key: "ArrowUp" | "ArrowRight" | "ArrowDown" | "ArrowLeft";
}

export interface FullTextState {
    fulltext_search_url: string;
    fulltext_search_results: Record<string, ItemDefinition>;
    fulltext_search_is_loading: boolean;
    fulltext_search_is_error: boolean;
    fulltext_search_is_available: boolean;
    fulltext_search_has_more_results: boolean;
}

export const FULLTEXT_MINIMUM_LENGTH_FOR_QUERY = 3;

export interface KeyboardNavigationState {
    programmatically_focused_element: Project | ItemDefinition | QuickLink | null;
}

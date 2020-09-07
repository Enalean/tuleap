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

import { ActionContext } from "vuex";
import { FocusFromHistoryPayload, FocusFromProjectPayload, State } from "./type";
import { get } from "../../../../themes/tlp/src/js/fetch-wrapper";
import { Project, UserHistory, UserHistoryEntry } from "../type";

export async function loadHistory(context: ActionContext<State, State>): Promise<void> {
    if (context.state.is_history_loaded) {
        return;
    }

    try {
        const response = await get(`/api/users/${context.state.user_id}/history`);
        const history: UserHistory = await response.json();
        context.commit("saveHistory", history);
    } catch (e) {
        context.commit("setErrorForHistory", true);
        throw e;
    }
}

export function changeFocusFromProject(
    context: ActionContext<State, State>,
    payload: FocusFromProjectPayload
): void {
    if (payload.key === "ArrowLeft") {
        return;
    }

    if (payload.key === "ArrowRight") {
        if (!context.state.is_history_loaded) {
            return;
        }

        if (context.state.is_history_in_error) {
            return;
        }

        if (context.getters.filtered_history.entries.length === 0) {
            return;
        }

        context.commit(
            "setProgrammaticallyFocusedElement",
            context.getters.filtered_history.entries[0]
        );
        return;
    }

    navigateInCollection(
        context,
        context.getters.filtered_projects,
        context.getters.filtered_projects.findIndex(
            (project: Project) => project.project_uri === payload.project.project_uri
        ),
        payload.key
    );
}

export function changeFocusFromHistory(
    context: ActionContext<State, State>,
    payload: FocusFromHistoryPayload
): void {
    if (payload.key === "ArrowRight") {
        return;
    }

    if (payload.key === "ArrowLeft") {
        if (context.getters.filtered_projects.length === 0) {
            return;
        }

        context.commit("setProgrammaticallyFocusedElement", context.getters.filtered_projects[0]);
        return;
    }

    navigateInCollection(
        context,
        context.getters.filtered_history.entries,
        context.getters.filtered_history.entries.findIndex(
            (entry: UserHistoryEntry) => entry.html_url === payload.entry.html_url
        ),
        payload.key
    );
}

function navigateInCollection(
    context: ActionContext<State, State>,
    collection: UserHistoryEntry[],
    current_index: number,
    key: "ArrowUp" | "ArrowDown"
): void {
    if (current_index === -1) {
        return;
    }

    let focused_index = current_index + (key === "ArrowUp" ? -1 : 1);
    const is_out_of_boundaries = typeof collection[focused_index] === "undefined";
    if (is_out_of_boundaries) {
        if (focused_index >= collection.length) {
            focused_index = 0;
        } else {
            focused_index = collection.length - 1;
        }
    }

    if (current_index === focused_index) {
        return;
    }

    context.commit("setProgrammaticallyFocusedElement", collection[focused_index]);
}

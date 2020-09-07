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

import { State } from "./type";
import { Project, UserHistory, UserHistoryEntry } from "../type";

export function updateFilterValue(state: State, value: string): void {
    state.filter_value = value;
}

export function saveHistory(state: State, history: UserHistory): void {
    state.is_history_loaded = true;
    state.is_loading_history = false;
    state.history = history;
}

export function setErrorForHistory(state: State, is_error: boolean): void {
    state.is_history_in_error = is_error;
}

export function setProgrammaticallyFocusedElement(
    state: State,
    element: Project | UserHistoryEntry
): void {
    state.programmatically_focused_element = element;
}

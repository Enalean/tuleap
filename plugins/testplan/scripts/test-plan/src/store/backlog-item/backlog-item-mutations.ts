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

import { AddTestDefinitionsToBacklogItemPayload, BacklogItemState } from "./type";
import { BacklogItem } from "../../type";

export function beginLoadingTestDefinition(state: BacklogItemState, item: BacklogItem): void {
    updateBacklogItem(state, item, (item) => ({ ...item, is_loading_test_definitions: true }));
}

export function endLoadingTestDefinition(state: BacklogItemState, item: BacklogItem): void {
    updateBacklogItem(state, item, (item) => ({ ...item, is_loading_test_definitions: false }));
}

export function addTestDefinitions(
    state: BacklogItemState,
    payload: AddTestDefinitionsToBacklogItemPayload
): void {
    updateBacklogItem(state, payload.backlog_item, (item) => ({
        ...item,
        test_definitions: item.test_definitions.concat(payload.test_definitions),
    }));
}

export function loadingErrorHasBeenCatchedForTestDefinition(
    state: BacklogItemState,
    item: BacklogItem
): void {
    updateBacklogItem(state, item, (item) => ({
        ...item,
        has_test_definitions_loading_error: true,
    }));
}

export function markTestDefinitionsAsBeingLoaded(state: BacklogItemState, item: BacklogItem): void {
    updateBacklogItem(state, item, (item) => ({ ...item, are_test_definitions_loaded: true }));
}

export function beginLoadingBacklogItems(state: BacklogItemState): void {
    state.is_loading = true;
}

export function endLoadingBacklogItems(state: BacklogItemState): void {
    state.is_loading = false;
}

export function addBacklogItems(state: BacklogItemState, collection: BacklogItem[]): void {
    state.backlog_items = state.backlog_items.concat(collection);
}

export function loadingErrorHasBeenCatched(state: BacklogItemState): void {
    state.has_loading_error = true;
}

export function expandBacklogItem(state: BacklogItemState, item: BacklogItem): void {
    updateBacklogItem(state, item, (item) => ({ ...item, is_expanded: true }));
}

export function collapseBacklogItem(state: BacklogItemState, item: BacklogItem): void {
    updateBacklogItem(state, item, (item) => ({ ...item, is_expanded: false }));
}

function updateBacklogItem(
    state: BacklogItemState,
    item: BacklogItem,
    getNewVersionOfItem: (item: BacklogItem) => BacklogItem
): void {
    const index = state.backlog_items.findIndex(
        (state_backlog_item: BacklogItem): boolean => state_backlog_item.id === item.id
    );
    if (index === -1) {
        throw Error("Unable to find the backlog item to update");
    }

    state.backlog_items.splice(index, 1, getNewVersionOfItem(state.backlog_items[index]));
}

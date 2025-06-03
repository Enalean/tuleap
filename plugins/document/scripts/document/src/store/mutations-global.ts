/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import type { Folder, Item, State } from "../type";
import { isFolder } from "../helpers/type-check-helper";

export function setRootTitle(state: State, root_title: string): void {
    state.root_title = root_title;
}

export function saveAscendantHierarchy(state: State, hierarchy: Array<Folder>): void {
    state.current_folder_ascendant_hierarchy = hierarchy;
}

export function resetAscendantHierarchy(state: State): void {
    state.current_folder_ascendant_hierarchy = [];
}

export function beginLoading(state: State): void {
    state.is_loading_folder = true;
}

export function stopLoading(state: State): void {
    state.is_loading_folder = false;
}

export function beginLoadingAscendantHierarchy(state: State): void {
    state.is_loading_ascendant_hierarchy = true;
}

export function stopLoadingAscendantHierarchy(state: State): void {
    state.is_loading_ascendant_hierarchy = false;
}

export function appendFolderToAscendantHierarchy(state: State, folder: Folder): void {
    const parent_index_in_hierarchy = state.current_folder_ascendant_hierarchy.findIndex(
        (item) => item.id === folder.parent_id,
    );

    if (parent_index_in_hierarchy !== -1) {
        state.current_folder_ascendant_hierarchy.push(folder);
        return;
    }

    const folder_index = state.folder_content.findIndex((item) => item.id === folder.id);
    const ascendants = state.folder_content.slice(0, folder_index);

    let next_parent_id = folder.parent_id;

    const direct_ascendants = ascendants.reduceRight<Folder[]>((accumulator, item) => {
        if (item.id === next_parent_id && isFolder(item)) {
            accumulator.push(item);

            next_parent_id = item.parent_id;
        }

        return accumulator;
    }, []);

    state.current_folder_ascendant_hierarchy.push(...direct_ascendants.reverse(), folder);
}

export function setCurrentFolder(state: State, folder: Folder): void {
    state.current_folder = folder;
}

export function replaceCurrentFolder(state: State, folder: Folder): void {
    state.current_folder = folder;
    const folder_in_hierarchy_index = state.current_folder_ascendant_hierarchy.findIndex(
        (item) => item.id === folder.id,
    );
    if (folder_in_hierarchy_index >= 0) {
        state.current_folder_ascendant_hierarchy[folder_in_hierarchy_index] = folder;
    }
}

export function beginLoadingCurrentlyPreviewedItem(state: State): void {
    state.is_loading_currently_previewed_item = true;
}

export function stopLoadingCurrentlyPreviewedItem(state: State): void {
    state.is_loading_currently_previewed_item = false;
}

export function updateCurrentlyPreviewedItem(state: State, item: Item | null): void {
    state.currently_previewed_item = item;
}

export function showPostDeletionNotification(state: State): void {
    state.show_post_deletion_notification = true;
}

export function hidePostDeletionNotification(state: State): void {
    state.show_post_deletion_notification = false;
}

export function toggleQuickLook(state: State, toggle: boolean): void {
    state.toggle_quick_look = toggle;
}

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

export {
    beginLoading,
    setRootTitle,
    stopLoading,
    saveAscendantHierarchy,
    resetAscendantHierarchy,
    beginLoadingAscendantHierarchy,
    stopLoadingAscendantHierarchy,
    appendFolderToAscendantHierarchy,
    setCurrentFolder,
    updateCurrentlyPreviewedItem,
    showPostDeletionNotification,
    hidePostDeletionNotification,
    replaceCurrentFolder,
    toggleQuickLook,
    beginLoadingCurrentlyPreviewedItem,
    stopLoadingCurrentlyPreviewedItem,
};

function setRootTitle(state, root_title) {
    state.root_title = root_title;
}

function saveAscendantHierarchy(state, hierarchy) {
    state.current_folder_ascendant_hierarchy = hierarchy;
}

function resetAscendantHierarchy(state) {
    state.current_folder_ascendant_hierarchy = [];
}

function beginLoading(state) {
    state.is_loading_folder = true;
}

function stopLoading(state) {
    state.is_loading_folder = false;
}

function beginLoadingAscendantHierarchy(state) {
    state.is_loading_ascendant_hierarchy = true;
}

function stopLoadingAscendantHierarchy(state) {
    state.is_loading_ascendant_hierarchy = false;
}

function appendFolderToAscendantHierarchy(state, folder) {
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

    const direct_ascendants = ascendants.reduceRight((accumulator, item) => {
        if (item.id === next_parent_id) {
            accumulator.push(item);

            next_parent_id = item.parent_id;
        }

        return accumulator;
    }, []);

    state.current_folder_ascendant_hierarchy.push(...direct_ascendants.reverse(), folder);
}

function setCurrentFolder(state, folder) {
    state.current_folder = folder;
}
function replaceCurrentFolder(state, folder) {
    state.current_folder = folder;
    const folder_in_hierarchy_index = state.current_folder_ascendant_hierarchy.findIndex(
        (item) => item.id === folder.id,
    );
    if (folder_in_hierarchy_index >= 0) {
        state.current_folder_ascendant_hierarchy[folder_in_hierarchy_index] = folder;
    }
}

function beginLoadingCurrentlyPreviewedItem(state) {
    state.is_loading_currently_previewed_item = true;
}

function stopLoadingCurrentlyPreviewedItem(state) {
    state.is_loading_currently_previewed_item = false;
}
function updateCurrentlyPreviewedItem(state, item) {
    state.currently_previewed_item = item;
}

function showPostDeletionNotification(state) {
    state.show_post_deletion_notification = true;
}

function hidePostDeletionNotification(state) {
    state.show_post_deletion_notification = false;
}

function toggleQuickLook(state, toggle) {
    state.toggle_quick_look = toggle;
}

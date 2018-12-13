/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
    initApp,
    resetErrors,
    saveFolderContent,
    appendSubFolderContent,
    foldFolderContent,
    unfoldFolderContent,
    setFolderLoadingError,
    stopLoading,
    switchFolderPermissionError,
    saveAscendantHierarchy,
    resetAscendantHierarchy,
    beginLoadingAscendantHierarchy,
    stopLoadingAscendantHierarchy,
    appendFolderToAscendantHierarchy,
    setCurrentFolder,
    setModalError,
    resetModalError
};

function saveFolderContent(state, folder_content) {
    state.folder_content = folder_content;
}

function appendSubFolderContent(state, [folder_id, sub_items]) {
    const folder_index = state.folder_content.findIndex(folder => folder.id === folder_id);
    const parent_folder = state.folder_content[folder_index];

    if (!parent_folder.level) {
        parent_folder.level = 0;
    }

    sub_items.forEach(item => {
        item.level = parent_folder.level + 1;
    });

    state.folder_content.splice(folder_index + 1, 0, ...sub_items);
}

function foldFolderContent(state, folder_id) {
    const children = getFolderUnfoldedDescendants(state, folder_id);
    const folded_content = children.map(item => item.id);

    state.folded_items_ids = state.folded_items_ids.concat(folded_content);

    state.folded_by_map[folder_id] = folded_content;
}

function unfoldFolderContent(state, folder_id) {
    const items_to_unfold = state.folded_by_map[folder_id];

    if (!items_to_unfold) {
        return;
    }

    state.folded_items_ids = state.folded_items_ids.filter(item => !items_to_unfold.includes(item));

    delete state.folded_by_map[folder_id];
}

function initApp(state, [user_id, project_id, user_is_admin, date_time_format, root_title]) {
    state.user_id = user_id;
    state.project_id = project_id;
    state.is_user_administrator = user_is_admin;
    state.date_time_format = date_time_format;
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

function resetErrors(state) {
    state.has_folder_permission_error = false;
    state.has_folder_loading_error = false;
    state.folder_loading_error = null;
}

function switchFolderPermissionError(state) {
    state.has_folder_permission_error = true;
}

function setFolderLoadingError(state, message) {
    state.has_folder_loading_error = true;
    state.folder_loading_error = message;
}

function appendFolderToAscendantHierarchy(state, folder) {
    const parent_index_in_hierarchy = state.current_folder_ascendant_hierarchy.findIndex(
        item => item.id === folder.parent_id
    );

    if (parent_index_in_hierarchy !== -1) {
        state.current_folder_ascendant_hierarchy.push(folder);
        return;
    }

    const folder_index = state.folder_content.findIndex(item => item.id === folder.id);
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

function getFolderUnfoldedDescendants(state, folder_id) {
    const children = state.folder_content.filter(item => item.parent_id === folder_id);

    const unfolded_descendants = [];

    children.forEach(child => {
        if (state.folded_by_map.hasOwnProperty(child.id)) {
            return;
        }

        unfolded_descendants.push(...getFolderUnfoldedDescendants(state, child.id));
    });

    return children.concat(unfolded_descendants);
}

function setModalError(state, error_message) {
    state.has_modal_error = true;
    state.modal_error = error_message;
}

function resetModalError(state) {
    state.has_modal_error = false;
    state.modal_error = null;
}

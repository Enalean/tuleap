/*
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

import Vue from "vue";
import { TYPE_FOLDER } from "../constants";

export {
    beginLoading,
    initApp,
    resetErrors,
    saveFolderContent,
    addJustCreatedItemToFolderContent,
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
    resetFoldedLists,
    resetModalError,
    removeCreatedPropertyOnItem,
    replaceUploadingFileWithActualFile,
    removeItemFromFolderContent,
    removeIsUnderConstruction,
    addFileInUploadsList,
    removeFileFromUploadsList,
    emptyFilesUploadsList,
    addFileToFoldedFolder,
    toggleCollapsedFolderHasUploadingContent,
    updateFolderProgressbar,
    initializeFolderProperties,
    resetFolderIsUploading
};

function saveFolderContent(state, folder_content) {
    state.folder_content = folder_content;
}

function addDocumentToTheRightPlace(state, new_item, parent) {
    const near_sibling_index = state.folder_content.findIndex(
        sibling =>
            sibling.type !== TYPE_FOLDER &&
            sibling.parent_id === new_item.parent_id &&
            sibling.title.localeCompare(new_item.title, undefined, {
                numeric: true
            }) >= 0
    );

    const has_no_sibling_and_no_parent = near_sibling_index === -1 && !parent;
    const has_a_parent_but_no_siblings = near_sibling_index === -1 && parent;

    if (has_no_sibling_and_no_parent) {
        state.folder_content.push(new_item);

        return;
    } else if (has_a_parent_but_no_siblings) {
        const document_siblings = state.folder_content.filter(
            item => item.parent_id === new_item.parent_id
        );

        let nearest_sibling;

        if (!document_siblings.length) {
            nearest_sibling = parent;
        } else {
            nearest_sibling = document_siblings[document_siblings.length - 1];
        }

        const nearest_sibling_index = state.folder_content.findIndex(
            item => item.id === nearest_sibling.id
        );

        state.folder_content.splice(nearest_sibling_index + 1, 0, new_item);

        return;
    }

    state.folder_content.splice(near_sibling_index, 0, new_item);
}

function addFolderToTheRightPlace(state, new_item, parent) {
    const folder_siblings = state.folder_content.filter(
        item => item.type === TYPE_FOLDER && item.parent_id === new_item.parent_id
    );

    let nearest_sibling = folder_siblings.find(sibling => {
        return (
            sibling.title.localeCompare(new_item.title, undefined, {
                numeric: true
            }) >= 0
        );
    });

    const is_the_last_of_its_siblings = !nearest_sibling && folder_siblings.length > 0;

    if (is_the_last_of_its_siblings) {
        nearest_sibling = folder_siblings[folder_siblings.length - 1];

        const nearest_sibling_index = state.folder_content.findIndex(
            item => item.id === nearest_sibling.id
        );

        state.folder_content.splice(nearest_sibling_index + 1, 0, new_item);
    } else if (nearest_sibling) {
        const nearest_sibling_index = state.folder_content.findIndex(
            item => item.id === nearest_sibling.id
        );

        state.folder_content.splice(nearest_sibling_index, 0, new_item);
    } else {
        if (parent) {
            const parent_index = state.folder_content.findIndex(item => item.id === parent.id);

            state.folder_content.splice(parent_index + 1, 0, new_item);
        } else {
            state.folder_content.splice(0, 0, new_item);
        }
    }
}

function addJustCreatedItemToFolderContent(state, new_item) {
    const parent = state.folder_content.find(parent => parent.id === new_item.parent_id);

    if (parent && !parent.level) {
        parent.level = 0;
    }

    new_item.level = parent ? parent.level + 1 : 0;

    if (new_item.type !== TYPE_FOLDER) {
        return addDocumentToTheRightPlace(state, new_item, parent);
    }

    addFolderToTheRightPlace(state, new_item, parent);
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

    const filtered_sub_items = sub_items.filter(
        item => state.folder_content.findIndex(existing_item => item.id === existing_item.id) === -1
    );

    state.folder_content.splice(folder_index + 1, 0, ...filtered_sub_items);
}

function foldFolderContent(state, folder_id) {
    const index = state.folder_content.findIndex(item => item.id === folder_id);

    if (index !== -1) {
        state.folder_content[index].is_expanded = false;
    }
    const children = getFolderUnfoldedDescendants(state, folder_id);
    const folded_content = children.map(item => item.id);

    state.folded_items_ids = state.folded_items_ids.concat(folded_content);

    state.folded_by_map[folder_id] = folded_content;
}

function addFileToFoldedFolder(state, [parent, item, should_display_fake_item]) {
    if (!should_display_fake_item) {
        if (!state.folded_by_map[parent.id]) {
            state.folded_by_map[parent.id] = [];
        }

        state.folded_by_map[parent.id].push(item.id);
        state.folded_items_ids.push(item.id);
    }
}

function unfoldFolderContent(state, folder_id) {
    const index = state.folder_content.findIndex(item => item.id === folder_id);

    if (index !== -1) {
        state.folder_content[index].is_expanded = true;
    }

    const items_to_unfold = state.folded_by_map[folder_id];

    if (!items_to_unfold) {
        return;
    }

    state.folded_items_ids = state.folded_items_ids.filter(item => !items_to_unfold.includes(item));

    delete state.folded_by_map[folder_id];
}

function resetFoldedLists(state) {
    state.folded_items_ids = [];
    state.folded_by_map = {};
}

function initApp(
    state,
    [
        user_id,
        project_id,
        user_is_admin,
        date_time_format,
        root_title,
        user_can_create_wiki,
        max_files_dragndrop,
        max_size_upload,
        is_under_construction,
        embedded_are_allowed
    ]
) {
    state.user_id = user_id;
    state.project_id = project_id;
    state.is_user_administrator = user_is_admin;
    state.date_time_format = date_time_format;
    state.root_title = root_title;
    state.user_can_create_wiki = user_can_create_wiki;
    state.max_files_dragndrop = max_files_dragndrop;
    state.max_size_upload = max_size_upload;
    state.is_under_construction = is_under_construction;
    state.embedded_are_allowed = embedded_are_allowed;
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

function removeCreatedPropertyOnItem(state, item) {
    Vue.delete(item, "created");
}

function replaceUploadingFileWithActualFile(state, [uploading_file, actual_file]) {
    const index = state.folder_content.findIndex(item => item.id === uploading_file.id);
    if (index === -1) {
        return;
    }

    state.folder_content.splice(index, 1, actual_file);
}

function removeItemFromFolderContent(state, item_to_remove) {
    const index = state.folder_content.findIndex(item => item.id === item_to_remove.id);
    if (index === -1) {
        return;
    }

    state.folder_content.splice(index, 1);
}

function removeIsUnderConstruction(state) {
    state.is_under_construction = false;
}

function addFileInUploadsList(state, file) {
    removeFileFromUploadsList(state, file);
    state.files_uploads_list.unshift(file);
}

function removeFileFromUploadsList(state, uploaded_file) {
    const file_index = state.files_uploads_list.findIndex(file => file.id === uploaded_file.id);
    if (file_index === -1) {
        return;
    }

    state.files_uploads_list.splice(file_index, 1);

    if (state.files_uploads_list.length === 0) {
        const folder_index = state.folder_content.findIndex(
            item => item.id === uploaded_file.parent_id
        );
        if (folder_index === -1) {
            return;
        }
        toggleCollapsedFolderHasUploadingContent(state, [
            state.folder_content[folder_index],
            false
        ]);
    }
}

function emptyFilesUploadsList(state) {
    state.files_uploads_list = [];
}

function initializeFolderProperties(state, folder) {
    const folder_index = state.folder_content.findIndex(item => item.id === folder.id);
    if (folder_index === -1) {
        return;
    }

    Vue.set(state.folder_content[folder_index], "is_uploading_in_collapsed_folder", false);
    Vue.set(state.folder_content[folder_index], "progress", null);
}

function toggleCollapsedFolderHasUploadingContent(state, [collapsed_folder, toggle]) {
    const folder_index = state.folder_content.findIndex(item => item.id === collapsed_folder.id);
    if (folder_index === -1) {
        return;
    }

    collapsed_folder.is_uploading_in_collapsed_folder = toggle;
    collapsed_folder.progress = 0;

    state.folder_content.splice(folder_index, 1, collapsed_folder);
}

function updateFolderProgressbar(state, collapsed_folder) {
    const folder_index = state.folder_content.findIndex(item => item.id === collapsed_folder.id);
    if (folder_index === -1) {
        return;
    }

    const children = state.files_uploads_list.reduce(function(progresses, item) {
        if (item.parent_id === collapsed_folder.id) {
            progresses.push(item.progress);
        }
        return progresses;
    }, []);

    if (!children.length) {
        return;
    }

    const total = children.reduce((total, item_progress) => total + item_progress, 0);
    collapsed_folder.progress = Math.trunc(total / children.length);

    state.folder_content.splice(folder_index, 1, collapsed_folder);
}

function resetFolderIsUploading(state, folder) {
    const folder_index = state.folder_content.findIndex(item => item.id === folder.id);
    if (folder_index === -1) {
        return;
    }

    folder.is_uploading_in_collapsed_folder = false;
    folder.progress = 0;

    state.folder_content.splice(folder_index, 1, folder);
}

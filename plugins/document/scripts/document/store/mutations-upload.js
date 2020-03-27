/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

export {
    addFileInUploadsList,
    removeFileFromUploadsList,
    emptyFilesUploadsList,
    initializeFolderProperties,
    resetFolderIsUploading,
    toggleCollapsedFolderHasUploadingContent,
    updateFolderProgressbar,
    removeVersionUploadProgress,
    replaceFileWithNewVersion,
    replaceLinkWithNewVersion,
    replaceWikiWithNewVersion,
    replaceEmbeddedFilesWithNewVersion,
    replaceLockInfoWithNewVersion,
};

function addFileInUploadsList(state, file) {
    removeFileFromUploadsList(state, file);
    state.files_uploads_list.unshift(file);
}

function removeVersionUploadProgress(state, uploaded_item) {
    uploaded_item.progress = null;
    uploaded_item.is_uploading_new_version = false;
    removeFileFromUploadsList(state, uploaded_item);
}

function removeFileFromUploadsList(state, uploaded_file) {
    const file_index = state.files_uploads_list.findIndex((file) => file.id === uploaded_file.id);
    if (file_index === -1) {
        return;
    }

    state.files_uploads_list.splice(file_index, 1);

    if (state.files_uploads_list.length === 0) {
        const folder_index = state.folder_content.findIndex(
            (item) => item.id === uploaded_file.parent_id
        );
        if (folder_index === -1) {
            return;
        }
        toggleCollapsedFolderHasUploadingContent(state, [
            state.folder_content[folder_index],
            false,
        ]);
    }
}

function emptyFilesUploadsList(state) {
    state.files_uploads_list = [];
}

function initializeFolderProperties(state, folder) {
    const folder_index = state.folder_content.findIndex((item) => item.id === folder.id);
    if (folder_index === -1) {
        return;
    }

    Vue.set(state.folder_content[folder_index], "is_uploading_in_collapsed_folder", false);
    Vue.set(state.folder_content[folder_index], "progress", null);
}

function toggleCollapsedFolderHasUploadingContent(state, [collapsed_folder, toggle]) {
    const folder_index = state.folder_content.findIndex((item) => item.id === collapsed_folder.id);
    if (folder_index === -1) {
        return;
    }

    collapsed_folder.is_uploading_in_collapsed_folder = toggle;
    collapsed_folder.progress = 0;

    state.folder_content.splice(folder_index, 1, collapsed_folder);
}

function updateFolderProgressbar(state, collapsed_folder) {
    const folder_index = state.folder_content.findIndex((item) => item.id === collapsed_folder.id);
    if (folder_index === -1) {
        return;
    }

    const children = state.files_uploads_list.reduce(function (progresses, item) {
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
    const folder_index = state.folder_content.findIndex((item) => item.id === folder.id);
    if (folder_index === -1) {
        return;
    }

    folder.is_uploading_in_collapsed_folder = false;
    folder.progress = 0;

    state.folder_content.splice(folder_index, 1, folder);
}

function replaceFileWithNewVersion(state, [existing_item, new_version]) {
    existing_item.file_properties = new_version.file_properties;
    existing_item.lock_info = new_version.lock_info;
}

function replaceLinkWithNewVersion(state, [existing_item, new_version]) {
    existing_item.link_properties = new_version.link_properties;
    existing_item.lock_info = new_version.lock_info;
}
function replaceWikiWithNewVersion(state, [existing_item, new_version]) {
    existing_item.lock_info = new_version.lock_info;
    existing_item.wiki_properties = new_version.wiki_properties;
}

function replaceEmbeddedFilesWithNewVersion(state, [existing_item, new_version]) {
    existing_item.lock_info = new_version.lock_info;
}

function replaceLockInfoWithNewVersion(state, [existing_item, lock_info]) {
    existing_item.lock_info = lock_info;
}

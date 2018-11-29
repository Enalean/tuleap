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
    saveDocumentRootId,
    saveFolderContent,
    setFolderLoadingError,
    stopLoading,
    switchFolderPermissionError,
    beginLoadingFolderTitle,
    stopLoadingFolderTitle,
    saveParents,
    resetParents,
    beginLoadingBreadcrumb,
    stopLoadingBreadcrumb,
    appendFolderToBreadcrumbs
};

function saveDocumentRootId(state, document_id) {
    state.project_root_document_id = document_id;
}

function saveFolderContent(state, folder_content) {
    state.folder_content = folder_content;
}

function initApp(state, [project_id, name, user_is_admin, date_time_format, root_title]) {
    state.project_id = project_id;
    state.project_name = name;
    state.is_user_administrator = user_is_admin;
    state.date_time_format = date_time_format;
    state.root_title = root_title;
}

function saveParents(state, parents) {
    state.current_folder_parents = parents;
}

function resetParents(state) {
    state.current_folder_parents = [];
}

function beginLoading(state) {
    state.is_loading_folder = true;
}

function stopLoading(state) {
    state.is_loading_folder = false;
}

function beginLoadingBreadcrumb(state) {
    state.is_loading_breadcrumb = true;
}

function stopLoadingBreadcrumb(state) {
    state.is_loading_breadcrumb = false;
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

function beginLoadingFolderTitle(state) {
    state.is_loading_folder_title = true;
}

function stopLoadingFolderTitle(state) {
    state.is_loading_folder_title = false;
}

function appendFolderToBreadcrumbs(state, folder) {
    state.current_folder_parents.push(folder);
}

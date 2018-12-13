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
    setFolderLoadingError,
    stopLoading,
    switchFolderPermissionError,
    saveAscendantHierarchy,
    resetAscendantHierarchy,
    beginLoadingAscendantHierarchy,
    stopLoadingAscendantHierarchy,
    appendFolderToAscendantHierarchy,
    setCurrentFolder
};

function saveFolderContent(state, folder_content) {
    state.folder_content = folder_content;
}

function initApp(state, [project_id, user_is_admin, date_time_format, root_title]) {
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
    state.current_folder_ascendant_hierarchy.push(folder);
}

function setCurrentFolder(state, folder) {
    state.current_folder = folder;
}

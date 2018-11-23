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

export default {
    saveDocumentRootId(state, document_id) {
        state.project_root_document_id = document_id;
    },

    saveFolderContent(state, folder_content) {
        state.folder_content = folder_content;
    },

    switchFolderPermissionError(state) {
        state.has_folder_permission_error = true;
    },

    setFolderLoadingError(state, message) {
        state.has_folder_loading_error = true;
        state.folder_loading_error = message;
    },

    switchLoadingFolder(state, status) {
        state.is_loading_folder = status;
    },

    initDocumentTree(state, [project_id, name, user_is_admin, user_locale]) {
        state.project_id = project_id;
        state.project_name = name;
        state.is_user_administrator = user_is_admin;
        state.user_locale = user_locale;
    }
};

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

import { getProject, getFolderContent, getItem, getParents } from "../api/rest-querier.js";

export const loadRootDocumentId = async context => {
    try {
        context.commit("beginLoading");
        const project = await getProject(context.state.project_id);
        const id = project.additional_informations.docman.root_item.id;

        context.commit("saveDocumentRootId", id);
        await loadFolderContent(context, id);
    } catch (exception) {
        return handleErrors(context, exception);
    } finally {
        context.commit("stopLoading");
    }
};

export const loadFolderContent = async (context, folder_id) => {
    try {
        context.commit("beginLoading");
        context.commit("saveFolderContent", []);

        const folder_content = await getFolderContent(folder_id);
        context.commit("saveFolderContent", folder_content);
    } catch (exception) {
        return handleErrors(context, exception);
    } finally {
        context.commit("stopLoading");
    }
};

export const loadBreadCrumbs = async (context, folder_id) => {
    const index_of_folder_in_parents = context.state.current_folder_parents.findIndex(
        parent => parent.id === folder_id
    );
    if (index_of_folder_in_parents !== -1) {
        const hierarchy = context.state.current_folder_parents.slice(
            0,
            index_of_folder_in_parents + 1
        );
        context.commit("saveParents", hierarchy);
        context.commit("setCurrentFolderTitle", hierarchy[hierarchy.length - 1].title);
        return;
    }

    try {
        context.commit("beginLoadingFolderTitle");
        context.commit("beginLoadingBreadcrumb");

        context.commit("resetParents");

        let parents = await getParents(folder_id);
        const current_folder = await getItem(folder_id);

        parents.shift();
        parents.push(current_folder);

        context.commit("setCurrentFolderTitle", current_folder.title);
        context.commit("saveParents", parents);
    } catch (exception) {
        return handleErrors(context, exception);
    } finally {
        context.commit("stopLoadingFolderTitle");
        context.commit("stopLoadingBreadcrumb");
    }
};

async function handleErrors(context, exception) {
    const status = exception.response.status;
    if (status === 403) {
        context.commit("switchFolderPermissionError");
        return;
    }

    const json = await exception.response.json();
    context.commit("setFolderLoadingError", getErrorMessage(json));
}

function getErrorMessage(error_json) {
    if (error_json.hasOwnProperty("error")) {
        if (error_json.error.hasOwnProperty("i18n_error_message")) {
            return error_json.error.i18n_error_message;
        }

        return error_json.error.message;
    }

    return "";
}

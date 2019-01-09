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

import {
    getItem,
    getProject,
    getFolderContent,
    getUserPreferencesForFolderInProject,
    patchUserPreferenciesForFolderInProject,
    deleteUserPreferenciesForFolderInProject,
    addNewDocument
} from "../api/rest-querier.js";

import { handleErrors, handleErrorsForModal } from "./actions-helpers/handle-errors.js";
import { loadFolderContent } from "./actions-helpers/load-folder-content.js";
import { loadAscendantHierarchy } from "./actions-helpers/load-ascendant-hierarchy.js";

export const loadRootFolder = async context => {
    try {
        context.commit("beginLoading");
        const project = await getProject(context.state.project_id);
        const root = project.additional_informations.docman.root_item;

        context.commit("setCurrentFolder", root);

        return await loadFolderContent(context, root.id, Promise.resolve(root));
    } catch (exception) {
        return handleErrors(context, exception);
    } finally {
        context.commit("stopLoading");
    }
};

export const getSubfolderContent = async (context, folder_id) => {
    try {
        const sub_items = await getFolderContent(folder_id);

        context.commit("appendSubFolderContent", [folder_id, sub_items]);
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

export const createNewDocument = async (context, [item, parent]) => {
    try {
        const item_reference = await addNewDocument(item, parent.id);

        const created_item = await getItem(item_reference.id);
        created_item.created = true;
        setTimeout(() => {
            context.commit("removeCreatedPropertyOnItem", created_item);
        }, 5000);

        return Promise.resolve(context.commit("addJustCreatedItemToFolderContent", created_item));
    } catch (exception) {
        return handleErrorsForModal(context, exception);
    }
};

export const loadFolder = (context, folder_id) => {
    const { is_folder_found_in_hierarchy, current_folder } = getCurrentFolder();
    const loading_current_folder_promise = getLoadingCurrentFolderPromise(current_folder);

    const promises = [loadFolderContent(context, folder_id, loading_current_folder_promise)];
    if (!is_folder_found_in_hierarchy) {
        promises.push(loadAscendantHierarchy(context, folder_id, loading_current_folder_promise));
    }

    return Promise.all(promises);

    function getCurrentFolder() {
        const index_of_folder_in_hierarchy = context.state.current_folder_ascendant_hierarchy.findIndex(
            item => item.id === folder_id
        );
        const is_folder_found_in_hierarchy = index_of_folder_in_hierarchy !== -1;
        const current_folder = is_folder_found_in_hierarchy
            ? switchToFolderWeFoundInHierarchy(index_of_folder_in_hierarchy)
            : context.state.current_folder;

        return {
            is_folder_found_in_hierarchy,
            current_folder
        };
    }

    function switchToFolderWeFoundInHierarchy(index_of_folder_in_hierarchy) {
        context.commit(
            "saveAscendantHierarchy",
            context.state.current_folder_ascendant_hierarchy.slice(
                0,
                index_of_folder_in_hierarchy + 1
            )
        );

        const folder_in_store = context.state.current_folder;
        if (
            folder_in_store !==
            context.state.current_folder_ascendant_hierarchy[index_of_folder_in_hierarchy]
        ) {
            const found_folder =
                context.state.current_folder_ascendant_hierarchy[index_of_folder_in_hierarchy];
            context.commit("setCurrentFolder", found_folder);

            return found_folder;
        }

        return folder_in_store;
    }

    function getLoadingCurrentFolderPromise(current_folder) {
        if (shouldWeRemotelyLoadTheFolder(current_folder, folder_id)) {
            return getItem(folder_id).then(folder => {
                context.commit("setCurrentFolder", folder);

                return folder;
            });
        }

        return Promise.resolve(context.state.current_folder);
    }

    function shouldWeRemotelyLoadTheFolder(current_folder) {
        return !current_folder || current_folder.id !== folder_id;
    }
};

export const getFolderShouldBeOpen = (context, folder_id) => {
    if (context.state.user_id === 0) {
        return { value: false };
    }

    try {
        return getUserPreferencesForFolderInProject(
            context.state.user_id,
            context.state.project_id,
            folder_id
        );
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

export const setUserPreferenciesForFolder = (context, [folder_id, should_be_closed]) => {
    if (context.state.user_id === 0) {
        return;
    }

    try {
        if (should_be_closed) {
            return deleteUserPreferenciesForFolderInProject(
                context.state.user_id,
                context.state.project_id,
                folder_id
            );
        }

        return patchUserPreferenciesForFolderInProject(
            context.state.user_id,
            context.state.project_id,
            folder_id
        );
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

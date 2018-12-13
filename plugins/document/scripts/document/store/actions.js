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

import { getItem, getProject } from "../api/rest-querier.js";
import { handleErrors } from "./actions-helpers/handle-errors.js";
import { loadFolderContent } from "./actions-helpers/load-folder-content.js";
import { loadAscendantHierarchy } from "./actions-helpers/load-ascendant-hierarchy.js";

export const loadRootFolder = async context => {
    try {
        context.commit("beginLoading");
        const project = await getProject(context.state.project_id);
        const root = project.additional_informations.docman.root_item;

        context.commit("setCurrentFolder", root);

        await loadFolderContent(context, root.id, Promise.resolve(root));
    } catch (exception) {
        return handleErrors(context, exception);
    } finally {
        context.commit("stopLoading");
    }
};

export const loadFolder = (context, folder_id) => {
    let current_folder = context.state.current_folder;

    const index_of_folder_in_hierarchy = context.state.current_folder_ascendant_hierarchy.findIndex(
        item => item.id === folder_id
    );
    const is_folder_found_in_hierarchy = index_of_folder_in_hierarchy !== -1;
    if (is_folder_found_in_hierarchy) {
        context.commit(
            "saveAscendantHierarchy",
            context.state.current_folder_ascendant_hierarchy.slice(
                0,
                index_of_folder_in_hierarchy + 1
            )
        );

        if (
            current_folder !==
            context.state.current_folder_ascendant_hierarchy[index_of_folder_in_hierarchy]
        ) {
            current_folder =
                context.state.current_folder_ascendant_hierarchy[index_of_folder_in_hierarchy];
            context.commit("setCurrentFolder", current_folder);
        }
    }

    let loading_current_folder_promise;

    if (!current_folder || current_folder.id !== folder_id) {
        loading_current_folder_promise = getItem(folder_id).then(folder => {
            context.commit("setCurrentFolder", folder);

            return folder;
        });
    } else {
        loading_current_folder_promise = Promise.resolve(context.state.current_folder);
    }

    loadFolderContent(context, folder_id, loading_current_folder_promise);
    if (!is_folder_found_in_hierarchy) {
        loadAscendantHierarchy(context, folder_id, loading_current_folder_promise);
    }
};

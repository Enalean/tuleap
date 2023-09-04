/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

import type { RestFolder } from "../api/rest-querier";
import {
    getDocumentManagerServiceInformation,
    getFolderContent,
    getItem,
    getItemsReferencingSameWikiPage,
    getParents,
} from "../api/rest-querier";
import { loadFolderContent } from "./actions-helpers/load-folder-content";
import { handleErrors, handleErrorsForDocument } from "./actions-helpers/handle-errors";
import type { Folder, Item, RootState, Wiki } from "../type";
import type { ActionContext } from "vuex";
import { loadAscendantHierarchy } from "./actions-helpers/load-ascendant-hierarchy";
import { isFolder } from "../helpers/type-check-helper";
import { convertFolderItemToFolder } from "../helpers/properties-helpers/metadata-to-properties";
import type { ItemPath } from "./actions-helpers/build-parent-paths";
import { buildItemPath } from "./actions-helpers/build-parent-paths";
import { USER_CANNOT_PROPAGATE_DELETION_TO_WIKI_SERVICE } from "../constants";

export interface RootActionsRetrieve {
    readonly loadRootFolder: typeof loadRootFolder;
    readonly getSubfolderContent: typeof getSubfolderContent;
    readonly loadDocumentWithAscendentHierarchy: typeof loadDocumentWithAscendentHierarchy;
    readonly loadDocument: typeof loadDocument;
    readonly loadFolder: typeof loadFolder;
    readonly getWikisReferencingSameWikiPage: typeof getWikisReferencingSameWikiPage;
}

export const loadRootFolder = async (
    context: ActionContext<RootState, RootState>,
): Promise<void> => {
    try {
        context.commit("beginLoading");
        const service = await getDocumentManagerServiceInformation(
            Number(context.state.configuration.project_id),
        );
        const root: RestFolder = await service.root_item;

        const folder = convertFolderItemToFolder(root);
        context.commit("setCurrentFolder", folder);

        return await loadFolderContent(context, root.id, Promise.resolve(folder));
    } catch (exception) {
        return handleErrors(context, exception);
    } finally {
        context.commit("stopLoading");
    }
};

export const getSubfolderContent = async (
    context: ActionContext<RootState, RootState>,
    folder_id: number,
): Promise<void> => {
    try {
        const sub_items = await getFolderContent(folder_id);

        return context.commit("appendSubFolderContent", [folder_id, sub_items]);
    } catch (exception) {
        return handleErrors(context, exception);
    }
};

export const loadDocumentWithAscendentHierarchy = async (
    context: ActionContext<RootState, RootState>,
    item_id: number,
): Promise<Item | void> => {
    try {
        const item = await getItem(item_id);
        const loading_current_folder_promise = getItem(Number(item.parent_id));
        await loadAscendantHierarchy(
            context,
            Number(item.parent_id),
            loading_current_folder_promise,
        );

        return item;
    } catch (exception) {
        return handleErrorsForDocument(context, exception);
    }
};

export const loadDocument = async (
    context: ActionContext<RootState, RootState>,
    item_id: number,
): Promise<Item | void> => {
    try {
        return await getItem(item_id);
    } catch (exception) {
        return handleErrorsForDocument(context, exception);
    }
};

export const loadFolder = (
    context: ActionContext<RootState, RootState>,
    folder_id: number,
): Promise<void[]> => {
    const { is_folder_found_in_hierarchy, current_folder } = getCurrentFolder();
    const loading_current_folder_promise = getLoadingCurrentFolderPromise(current_folder);

    const promises = [loadFolderContent(context, folder_id, loading_current_folder_promise)];
    if (!is_folder_found_in_hierarchy) {
        promises.push(loadAscendantHierarchy(context, folder_id, loading_current_folder_promise));
    }

    return Promise.all(promises);

    function getCurrentFolder(): { is_folder_found_in_hierarchy: boolean; current_folder: Folder } {
        const index_of_folder_in_hierarchy =
            context.state.current_folder_ascendant_hierarchy.findIndex(
                (item) => item.id === folder_id,
            );
        const is_folder_found_in_hierarchy = index_of_folder_in_hierarchy !== -1;
        const current_folder = is_folder_found_in_hierarchy
            ? switchToFolderWeFoundInHierarchy(index_of_folder_in_hierarchy)
            : context.state.current_folder;

        return {
            is_folder_found_in_hierarchy,
            current_folder,
        };
    }

    function switchToFolderWeFoundInHierarchy(index_of_folder_in_hierarchy: number): Folder {
        context.commit(
            "saveAscendantHierarchy",
            context.state.current_folder_ascendant_hierarchy.slice(
                0,
                index_of_folder_in_hierarchy + 1,
            ),
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

    function getLoadingCurrentFolderPromise(current_folder: Folder): Promise<Folder> {
        if (shouldWeRemotelyLoadTheFolder(current_folder)) {
            return getItem(folder_id).then((folder): Folder => {
                context.commit("setCurrentFolder", folder);

                if (!isFolder(folder)) {
                    throw Error("Folder is not a folder");
                }

                return folder;
            });
        }

        return Promise.resolve(context.state.current_folder);
    }

    function shouldWeRemotelyLoadTheFolder(current_folder: Folder): boolean {
        return !current_folder || current_folder.id !== folder_id;
    }
};

export const getWikisReferencingSameWikiPage = async (
    context: ActionContext<RootState, RootState>,
    item: Wiki,
): Promise<ItemPath[] | null> => {
    if (item.wiki_properties.page_id === null) {
        return [];
    }

    try {
        const wiki_page_referencers = await getItemsReferencingSameWikiPage(
            item.wiki_properties.page_id,
        );

        return await Promise.all(
            wiki_page_referencers.map((item) =>
                getParents(item.item_id).then((parents) => buildItemPath(item, parents)),
            ),
        );
    } catch (exception) {
        return USER_CANNOT_PROPAGATE_DELETION_TO_WIKI_SERVICE;
    }
};

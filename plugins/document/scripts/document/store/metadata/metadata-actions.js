/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    getProjectMetadata,
    putEmbeddedFileMetadata,
    putEmptyDocumentMetadata,
    putFileMetadata,
    putFolderDocumentMetadata,
    putLinkMetadata,
    putWikiMetadata,
} from "../../api/metadata-rest-querier";
import { handleErrors, handleErrorsForModal } from "../actions-helpers/handle-errors";
import { getCustomMetadata } from "../../helpers/metadata-helpers/custom-metadata-helper";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../constants";
import { getItem, getItemWithSize } from "../../api/rest-querier";
import Vue from "vue";
import { formatCustomMetadataForFolderUpdate } from "../../helpers/metadata-helpers/update-data-transformatter-helper";

export const loadProjectMetadata = async (context, [global_context]) => {
    try {
        const project_metadata = await getProjectMetadata(
            global_context.state.configuration.project_id
        );

        context.commit("saveProjectMetadata", project_metadata);
    } catch (exception) {
        await handleErrors(global_context, exception);
    }
};

export const updateMetadata = async (context, [item, item_to_update, current_folder]) => {
    const custom_metadata = getCustomMetadata(item_to_update.metadata);
    let obsolescence_date = item_to_update.obsolescence_date;
    if (obsolescence_date === "") {
        obsolescence_date = null;
    }
    try {
        switch (item_to_update.type) {
            case TYPE_FILE:
                await putFileMetadata(
                    item_to_update.id,
                    item_to_update.title,
                    item_to_update.description,
                    item_to_update.owner.id,
                    item_to_update.status,
                    obsolescence_date,
                    custom_metadata
                );
                break;
            case TYPE_EMBEDDED:
                await putEmbeddedFileMetadata(
                    item_to_update.id,
                    item_to_update.title,
                    item_to_update.description,
                    item_to_update.owner.id,
                    item_to_update.status,
                    obsolescence_date,
                    custom_metadata
                );
                break;
            case TYPE_LINK:
                await putLinkMetadata(
                    item_to_update.id,
                    item_to_update.title,
                    item_to_update.description,
                    item_to_update.owner.id,
                    item_to_update.status,
                    obsolescence_date,
                    custom_metadata
                );
                break;
            case TYPE_WIKI:
                await putWikiMetadata(
                    item_to_update.id,
                    item_to_update.title,
                    item_to_update.description,
                    item_to_update.owner.id,
                    item_to_update.status,
                    obsolescence_date,
                    custom_metadata
                );
                break;
            case TYPE_EMPTY:
                await putEmptyDocumentMetadata(
                    item_to_update.id,
                    item_to_update.title,
                    item_to_update.description,
                    item_to_update.owner.id,
                    item_to_update.status,
                    obsolescence_date,
                    custom_metadata
                );
                break;
            case TYPE_FOLDER:
                await putFolderDocumentMetadata(
                    item_to_update.id,
                    item_to_update.title,
                    item_to_update.description,
                    item_to_update.owner.id,
                    {
                        value: item_to_update.status.value,
                        recursion: item_to_update.status.recursion,
                    },
                    obsolescence_date,
                    custom_metadata
                );
                break;
            default:
                break;
        }
        const updated_item = await getItem(item.id);

        if (item.id === current_folder.id) {
            context.commit("replaceCurrentFolder", updated_item, { root: true });
            await context.dispatch("loadFolder", item.id, { root: true });
        } else {
            Vue.set(updated_item, "updated", true);
            context.commit("removeItemFromFolderContent", updated_item, { root: true });
            context.commit("addJustCreatedItemToFolderContent", updated_item, { root: true });
            context.commit("updateCurrentItemForQuickLokDisplay", updated_item, { root: true });
        }
    } catch (exception) {
        await handleErrorsForModal(context, exception);
    }
};

export const updateFolderMetadata = async (
    context,
    [item, item_to_update, current_folder, metadata_list_to_update, recursion_option]
) => {
    formatCustomMetadataForFolderUpdate(item_to_update, metadata_list_to_update, recursion_option);
    await updateMetadata(context, [item, item_to_update, current_folder]);
};

export const getFolderProperties = async (context, [folder_item]) => {
    try {
        const { folder_properties } = await getItemWithSize(folder_item.id);

        return folder_properties;
    } catch (exception) {
        await context.dispatch("error/handleGlobalModalError", exception);
        return null;
    }
};

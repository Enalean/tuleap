/*
 * Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
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
import { getCustomProperties } from "../../helpers/properties-helpers/custom-properties-helper";
import { getItem, getItemWithSize } from "../../api/rest-querier";
import Vue from "vue";
import { formatCustomPropertiesForFolderUpdate } from "../../helpers/properties-helpers/update-data-transformatter-helper";
import type { ActionContext } from "vuex";
import type { Folder, FolderProperties, Item, RootState } from "../../type";
import type { FolderStatus, MetadataState } from "./module";
import {
    isEmbedded,
    isFile,
    isLink,
    isWiki,
    isEmpty,
    isFolder,
} from "../../helpers/type-check-helper";

export const loadProjectMetadata = async (
    context: ActionContext<MetadataState, RootState>
): Promise<void> => {
    try {
        const project_metadata = await getProjectMetadata(
            parseInt(context.rootState.configuration.project_id, 10)
        );

        context.commit("saveProjectMetadata", project_metadata);
    } catch (exception) {
        await context.dispatch("error/handleGlobalModalError", exception, { root: true });
    }
};

interface updateMetadataPayload {
    item: Item;
    item_to_update: Item;
    current_folder: Folder;
}

export const updateMetadata = async (
    context: ActionContext<MetadataState, RootState>,
    payload: updateMetadataPayload
): Promise<void> => {
    const item_to_update = payload.item_to_update;
    const custom_metadata = getCustomProperties(item_to_update.metadata);
    const item_obsolescence_date = item_to_update.obsolescence_date;
    let obsolescence_date = null;
    if (item_obsolescence_date) {
        obsolescence_date = item_obsolescence_date;
    }
    try {
        if (isFile(item_to_update)) {
            await putFileMetadata(
                item_to_update.id,
                item_to_update.title,
                item_to_update.description,
                item_to_update.owner.id,
                item_to_update.status,
                obsolescence_date,
                custom_metadata
            );
        } else if (isEmbedded(item_to_update)) {
            await putEmbeddedFileMetadata(
                item_to_update.id,
                item_to_update.title,
                item_to_update.description,
                item_to_update.owner.id,
                item_to_update.status,
                obsolescence_date,
                custom_metadata
            );
        } else if (isLink(item_to_update)) {
            await putLinkMetadata(
                item_to_update.id,
                item_to_update.title,
                item_to_update.description,
                item_to_update.owner.id,
                item_to_update.status,
                obsolescence_date,
                custom_metadata
            );
        } else if (isWiki(item_to_update)) {
            await putWikiMetadata(
                item_to_update.id,
                item_to_update.title,
                item_to_update.description,
                item_to_update.owner.id,
                item_to_update.status,
                obsolescence_date,
                custom_metadata
            );
        } else if (isEmpty(item_to_update)) {
            await putEmptyDocumentMetadata(
                item_to_update.id,
                item_to_update.title,
                item_to_update.description,
                item_to_update.owner.id,
                item_to_update.status,
                obsolescence_date,
                custom_metadata
            );
        } else if (isFolder(item_to_update)) {
            const status: FolderStatus = {
                value: item_to_update.status.value,
                recursion: item_to_update.status.recursion,
            };
            await putFolderDocumentMetadata(
                item_to_update.id,
                item_to_update.title,
                item_to_update.description,
                item_to_update.owner.id,
                status,
                obsolescence_date,
                custom_metadata
            );
        }

        const updated_item = await getItem(payload.item.id);

        if (payload.item.id === payload.current_folder.id) {
            context.commit("replaceCurrentFolder", updated_item, { root: true });
            await context.dispatch("loadFolder", payload.item.id, { root: true });
        } else {
            Vue.set(updated_item, "updated", true);
            context.commit("removeItemFromFolderContent", updated_item, { root: true });
            context.commit("addJustCreatedItemToFolderContent", updated_item, { root: true });
            context.commit("updateCurrentItemForQuickLokDisplay", updated_item, { root: true });
        }
    } catch (exception) {
        await context.dispatch("error/handleGlobalModalError", exception, { root: true });
    }
};

interface updateFolderMetadataPayload {
    item: Folder;
    item_to_update: Folder;
    current_folder: Folder;
    metadata_list_to_update: Array<string>;
    recursion_option: string;
}

export const updateFolderMetadata = async (
    context: ActionContext<MetadataState, RootState>,
    payload: updateFolderMetadataPayload
): Promise<void> => {
    formatCustomPropertiesForFolderUpdate(
        payload.item_to_update,
        payload.metadata_list_to_update,
        payload.recursion_option
    );
    const update_payload: updateMetadataPayload = {
        item: payload.item,
        item_to_update: payload.item_to_update,
        current_folder: payload.current_folder,
    };
    await updateMetadata(context, update_payload);
};

export const getFolderProperties = async (
    context: ActionContext<MetadataState, RootState>,
    folder_item: Folder
): Promise<FolderProperties | null> => {
    try {
        const folder = await getItemWithSize(folder_item.id);
        return folder.folder_properties;
    } catch (exception) {
        await context.dispatch("error/handleGlobalModalError", exception);
        return null;
    }
};

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
    getProjectProperties,
    putEmbeddedFileProperties,
    putEmptyDocumentProperties,
    putFileProperties,
    putFolderDocumentProperties,
    putLinkProperties,
    putWikiProperties,
} from "../../api/properties-rest-querier";
import { getCustomProperties } from "../../helpers/properties-helpers/custom-properties-helper";
import { getItem, getItemWithSize } from "../../api/rest-querier";
import { formatCustomPropertiesForFolderUpdate } from "../../helpers/properties-helpers/update-data-transformatter-helper";
import type { ActionContext } from "vuex";
import type { FolderStatus, Folder, FolderProperties, Item, RootState } from "../../type";
import type { PropertiesState } from "./module";
import {
    isEmbedded,
    isFile,
    isLink,
    isWiki,
    isEmpty,
    isFolder,
} from "../../helpers/type-check-helper";
import emitter from "../../helpers/emitter";

export interface PropertiesActions {
    readonly loadProjectProperties: typeof loadProjectProperties;
    readonly updateProperties: typeof updateProperties;
    readonly updateFolderProperties: typeof updateFolderProperties;
    readonly getFolderProperties: typeof getFolderProperties;
}

export const loadProjectProperties = async (
    context: ActionContext<PropertiesState, RootState>,
): Promise<void> => {
    try {
        const project_properties = await getProjectProperties(
            parseInt(context.rootState.configuration.project_id, 10),
        );

        context.commit("saveProjectProperties", project_properties);
    } catch (exception) {
        await context.dispatch("error/handleGlobalModalError", exception, { root: true });
    }
};

interface updatePropertiesPayload {
    item: Item;
    item_to_update: Item;
    current_folder: Folder;
}

export const updateProperties = async (
    context: ActionContext<PropertiesState, RootState>,
    payload: updatePropertiesPayload,
): Promise<void> => {
    const item_to_update = payload.item_to_update;
    const custom_properties = getCustomProperties(item_to_update);
    const item_obsolescence_date = item_to_update.obsolescence_date;
    let obsolescence_date = null;
    if (item_obsolescence_date) {
        obsolescence_date = item_obsolescence_date;
    }
    try {
        if (isFile(item_to_update)) {
            await putFileProperties(
                item_to_update.id,
                item_to_update.title,
                item_to_update.description,
                item_to_update.owner.id,
                item_to_update.status,
                obsolescence_date,
                custom_properties,
            );
        } else if (isEmbedded(item_to_update)) {
            await putEmbeddedFileProperties(
                item_to_update.id,
                item_to_update.title,
                item_to_update.description,
                item_to_update.owner.id,
                item_to_update.status,
                obsolescence_date,
                custom_properties,
            );
        } else if (isLink(item_to_update)) {
            await putLinkProperties(
                item_to_update.id,
                item_to_update.title,
                item_to_update.description,
                item_to_update.owner.id,
                item_to_update.status,
                obsolescence_date,
                custom_properties,
            );
        } else if (isWiki(item_to_update)) {
            await putWikiProperties(
                item_to_update.id,
                item_to_update.title,
                item_to_update.description,
                item_to_update.owner.id,
                item_to_update.status,
                obsolescence_date,
                custom_properties,
            );
        } else if (isEmpty(item_to_update)) {
            await putEmptyDocumentProperties(
                item_to_update.id,
                item_to_update.title,
                item_to_update.description,
                item_to_update.owner.id,
                item_to_update.status,
                obsolescence_date,
                custom_properties,
            );
        } else if (isFolder(item_to_update)) {
            const is_status_property_used: boolean =
                context.rootState.configuration.is_status_property_used;

            let recursion = "none";
            if (is_status_property_used) {
                recursion = item_to_update.status.recursion;
            }
            const status: FolderStatus = {
                value: item_to_update.status.value,
                recursion: recursion,
            };

            await putFolderDocumentProperties(
                item_to_update.id,
                item_to_update.title,
                item_to_update.description,
                item_to_update.owner.id,
                status,
                obsolescence_date,
                custom_properties,
            );
        }

        const updated_item = await getItem(payload.item.id);

        emitter.emit("item-properties-have-just-been-updated");

        if (payload.item.id === payload.current_folder.id) {
            context.commit("replaceCurrentFolder", updated_item, { root: true });
            await context.dispatch("loadFolder", payload.item.id, { root: true });
        } else {
            updated_item.updated = true;
            context.commit("removeItemFromFolderContent", updated_item, { root: true });
            context.commit("addJustCreatedItemToFolderContent", updated_item, { root: true });
            context.commit("updateCurrentItemForQuickLokDisplay", updated_item, { root: true });
        }
    } catch (exception) {
        await context.dispatch("error/handleErrorsForModal", exception, { root: true });
        throw exception;
    }
};

interface updateFolderPropertiesPayload {
    item: Folder;
    item_to_update: Folder;
    current_folder: Folder;
    properties_to_update: Array<string>;
    recursion_option: string;
}

export const updateFolderProperties = async (
    context: ActionContext<PropertiesState, RootState>,
    payload: updateFolderPropertiesPayload,
): Promise<void> => {
    const updated_item = formatCustomPropertiesForFolderUpdate(
        payload.item_to_update,
        payload.properties_to_update,
        payload.recursion_option,
    );
    const update_payload: updatePropertiesPayload = {
        item: payload.item,
        item_to_update: updated_item,
        current_folder: payload.current_folder,
    };
    await updateProperties(context, update_payload);
};

export const getFolderProperties = async (
    context: ActionContext<PropertiesState, RootState>,
    folder_item: Folder,
): Promise<FolderProperties | null> => {
    try {
        const folder = await getItemWithSize(folder_item.id);
        return folder.folder_properties;
    } catch (exception) {
        await context.dispatch("error/handleGlobalModalError", exception);
        return null;
    }
};

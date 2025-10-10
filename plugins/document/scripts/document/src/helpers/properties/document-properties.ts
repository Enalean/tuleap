/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { Folder, FolderProperties, FolderStatus, Item, Property, RootState } from "../../type";
import type { ActionContext } from "vuex";
import { getItem, getItemWithSize } from "../../api/rest-querier";
import { formatCustomPropertiesForFolderUpdate } from "../properties-helpers/update-data-transformatter-helper";
import { getCustomProperties } from "../properties-helpers/custom-properties-helper";
import {
    isEmbedded,
    isEmpty,
    isFile,
    isFolder,
    isLink,
    isOtherType,
    isWiki,
} from "../type-check-helper";
import {
    getProjectProperties,
    putEmbeddedFileProperties,
    putEmptyDocumentProperties,
    putFileProperties,
    putFolderDocumentProperties,
    putLinkProperties,
    putOtherTypeDocumentProperties,
    putWikiProperties,
} from "../../api/properties-rest-querier";
import emitter from "../emitter";
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";

export type DocumentProperties = {
    getFolderProperties(
        context: ActionContext<RootState, RootState>,
        folder_item: Folder,
    ): Promise<FolderProperties | null>;
    updateFolderProperties(
        context: ActionContext<RootState, RootState>,
        item: Folder,
        item_to_update: Folder,
        current_folder: Folder,
        properties_to_update: Array<string>,
        recursion_option: string,
        is_status_property_used: boolean,
    ): Promise<void>;
    updateProperties(
        context: ActionContext<RootState, RootState>,
        item: Item,
        item_to_update: Item,
        current_folder: Folder,
        is_status_property_used: boolean,
    ): Promise<void>;
    loadProjectProperties(
        context: ActionContext<RootState, RootState>,
        project_id: number,
    ): ResultAsync<Array<Property>, Fault>;
};

export const getDocumentProperties = (): DocumentProperties => {
    const updateProperties = async (
        context: ActionContext<RootState, RootState>,
        item: Item,
        item_to_update: Item,
        current_folder: Folder,
        is_status_property_used: boolean,
    ): Promise<void> => {
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
            } else if (isOtherType(item_to_update)) {
                await putOtherTypeDocumentProperties(
                    item_to_update.id,
                    item_to_update.title,
                    item_to_update.description,
                    item_to_update.owner.id,
                    item_to_update.status,
                    obsolescence_date,
                    custom_properties,
                );
            } else if (isFolder(item_to_update)) {
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

            const updated_item = await getItem(item.id);

            emitter.emit("item-properties-have-just-been-updated");

            if (item.id === current_folder.id) {
                context.commit("replaceCurrentFolder", updated_item, { root: true });
                await context.dispatch("loadFolder", item.id, { root: true });
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

    return {
        async getFolderProperties(
            context: ActionContext<RootState, RootState>,
            folder_item: Folder,
        ): Promise<FolderProperties | null> {
            try {
                const folder = await getItemWithSize(folder_item.id);
                return folder.folder_properties;
            } catch (exception) {
                await context.dispatch("error/handleGlobalModalError", exception);
                return null;
            }
        },
        async updateFolderProperties(
            context: ActionContext<RootState, RootState>,
            item: Folder,
            item_to_update: Folder,
            current_folder: Folder,
            properties_to_update: Array<string>,
            recursion_option: string,
            is_status_property_used: boolean,
        ): Promise<void> {
            const updated_item = formatCustomPropertiesForFolderUpdate(
                item_to_update,
                properties_to_update,
                recursion_option,
            );
            await updateProperties(
                context,
                item,
                updated_item,
                current_folder,
                is_status_property_used,
            );
        },
        updateProperties,
        loadProjectProperties(
            context: ActionContext<RootState, RootState>,
            project_id: number,
        ): ResultAsync<Array<Property>, Fault> {
            return getProjectProperties(project_id).mapErr((fault) => {
                context.dispatch("error/handleGlobalModalError", fault, { root: true });
                return fault;
            });
        },
    };
};

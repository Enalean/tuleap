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

import { createNewFile } from "./actions-helpers/create-new-file";
import {
    addNewEmbedded,
    addNewEmpty,
    addNewFolder,
    addNewLink,
    addNewWiki,
    getItem,
} from "../api/rest-querier";
import type { ActionContext } from "vuex";
import type { CreatedItem, Folder, Item, RootState, State } from "../type";
import {
    isEmbedded,
    isEmpty,
    isFile,
    isFolder,
    isLink,
    isWiki,
} from "../helpers/type-check-helper";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import emitter from "../helpers/emitter";
import { getErrorMessage } from "../helpers/properties-helpers/error-handler-helper";
import { flagItemAsCreated } from "./actions-helpers/flag-item-as-created";

export interface RootActionsCreate {
    readonly createNewItem: typeof createNewItem;
    readonly addNewUploadFile: typeof addNewUploadFile;
    readonly adjustItemToContentAfterItemCreationInAFolder: typeof adjustItemToContentAfterItemCreationInAFolder;
}

export const createNewItem = async (
    context: ActionContext<RootState, RootState>,
    [item, parent, current_folder]: [Item, Folder, Folder]
): Promise<CreatedItem | undefined> => {
    try {
        let should_display_item = true;
        let item_reference;

        const item_to_create = JSON.parse(JSON.stringify(item));
        if (item_to_create.obsolescence_date === "") {
            item_to_create.obsolescence_date = null;
        }
        if (item_to_create.properties) {
            item_to_create.metadata = item_to_create.properties;
        }

        if (isFile(item)) {
            if (!parent.is_expanded && parent.id !== current_folder.id) {
                should_display_item = false;
            }
            item_to_create.file_properties = item.file_properties;
            item_reference = await createNewFile(
                context,
                item_to_create,
                parent,
                should_display_item
            );
            return item_reference;
        }
        if (isFolder(item)) {
            item_reference = await addNewFolder(item_to_create, parent.id);
        } else if (isEmpty(item)) {
            item_reference = await addNewEmpty(item_to_create, parent.id);
        } else if (isWiki(item)) {
            item_reference = await addNewWiki(item_to_create, parent.id);
        } else if (isEmbedded(item)) {
            item_reference = await addNewEmbedded(item_to_create, parent.id);
        } else if (isLink(item)) {
            item_reference = await addNewLink(item_to_create, parent.id);
        } else {
            await context.dispatch(
                "error/handleErrorsForModal",
                new Error("Item type " + item_to_create.type + " is not supported for creation")
            );
            return undefined;
        }
        emitter.emit("new-item-has-just-been-created", item_reference);
        await adjustItemToContentAfterItemCreationInAFolder(context, {
            parent,
            current_folder,
            item_id: item_reference.id,
        });
        return item_reference;
    } catch (exception) {
        await context.dispatch("error/handleErrorsForModal", exception);
    }
};

export const addNewUploadFile = async (
    context: ActionContext<RootState, RootState>,
    [dropped_file, parent, title, description, should_display_fake_item]: [
        File,
        Folder,
        string,
        string,
        boolean
    ]
): Promise<void> => {
    try {
        const item = {
            title,
            description,
            file_properties: { file: dropped_file },
            properties: null,
        };
        await createNewFile(context, item, parent, should_display_fake_item);
    } catch (exception) {
        context.commit("toggleCollapsedFolderHasUploadingContent", {
            collapsed_folder: parent,
            toggle: false,
        });
        if (exception instanceof FetchWrapperError) {
            const error_json = await exception.response.json();
            throw getErrorMessage(error_json);
        }
        throw exception;
    }
};

export interface AdjustItemPayload {
    parent: Folder;
    current_folder: Folder;
    item_id: number;
}

export async function adjustItemToContentAfterItemCreationInAFolder(
    context: ActionContext<State, State>,
    payload: AdjustItemPayload
): Promise<void> {
    const created_item = await getItem(payload.item_id);

    context.commit("removeItemFromFolderContent", created_item);

    flagItemAsCreated(context, created_item);

    if (!payload.parent.is_expanded && payload.parent.id !== payload.current_folder.id) {
        context.commit("addDocumentToFoldedFolder", [payload.parent, created_item, false]);
    }

    return Promise.resolve(context.commit("addJustCreatedItemToFolderContent", created_item));
}

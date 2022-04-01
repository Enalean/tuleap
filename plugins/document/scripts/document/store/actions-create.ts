/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
} from "../api/rest-querier";
import { adjustItemToContentAfterItemCreationInAFolder } from "./actions-helpers/adjust-item-to-content-after-item-creation-in-folder";
import type { ActionContext } from "vuex";
import type { Folder, Item, RootState } from "../type";
import {
    isEmbedded,
    isEmpty,
    isFile,
    isFolder,
    isLink,
    isWiki,
} from "../helpers/type-check-helper";
import { getErrorMessage } from "./actions-helpers/handle-errors";
import { FetchWrapperError } from "tlp";

export const createNewItem = async (
    context: ActionContext<RootState, RootState>,
    [item, parent, current_folder]: [Item, Folder, Folder]
): Promise<void> => {
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
            await createNewFile(context, item_to_create, parent, should_display_item);
            return;
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
            return;
        }
        await adjustItemToContentAfterItemCreationInAFolder(
            context,
            parent,
            current_folder,
            item_reference.id
        );
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
        context.commit("toggleCollapsedFolderHasUploadingContent", [parent, false]);
        if (exception instanceof FetchWrapperError) {
            const error_json = await exception.response.json();
            throw getErrorMessage(error_json);
        }
        throw exception;
    }
};

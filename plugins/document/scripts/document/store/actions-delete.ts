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

import {
    deleteEmbeddedFile,
    deleteEmptyDocument,
    deleteFile,
    deleteFolder,
    deleteLink,
    deleteWiki,
} from "../api/rest-querier";
import { handleErrorsForDeletionModal } from "./actions-helpers/handle-errors";
import type { ActionContext, Store } from "vuex";
import type { Item, RootState } from "../type";
import {
    isEmbedded,
    isEmpty,
    isFile,
    isFolder,
    isLink,
    isWiki,
} from "../helpers/type-check-helper";
import emitter from "../helpers/emitter";

export interface RootActionsDelete {
    readonly deleteItem: typeof deleteItem;
}

export interface DeleteItemPayload {
    item: Item;
    clipboard: Store<"clipboard">;
    additional_wiki_options?: {
        delete_associated_wiki_page: boolean;
    };
}

export const deleteItem = async (
    context: ActionContext<RootState, RootState>,
    payload: DeleteItemPayload,
): Promise<void> => {
    try {
        if (isFile(payload.item)) {
            await deleteFile(payload.item);
        } else if (isLink(payload.item)) {
            await deleteLink(payload.item);
        } else if (isEmbedded(payload.item)) {
            await deleteEmbeddedFile(payload.item);
        } else if (isWiki(payload.item) && payload.additional_wiki_options) {
            await deleteWiki(payload.item, payload.additional_wiki_options);
        } else if (isEmpty(payload.item)) {
            await deleteEmptyDocument(payload.item);
        } else if (isFolder(payload.item)) {
            await deleteFolder(payload.item);
        }

        emitter.emit("item-has-just-been-deleted");

        payload.clipboard.emptyClipboardAfterItemDeletion(payload.item);
        context.commit("removeItemFromFolderContent", payload.item);
        context.commit("showPostDeletionNotification");

        return Promise.resolve();
    } catch (exception) {
        return handleErrorsForDeletionModal(context, exception, payload.item);
    }
};

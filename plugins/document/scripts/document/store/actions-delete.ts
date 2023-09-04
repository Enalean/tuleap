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
import type { ActionContext } from "vuex";
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

export const deleteItem = async (
    context: ActionContext<RootState, RootState>,
    [item, additional_wiki_options]: [
        Item,
        {
            delete_associated_wiki_page: boolean;
        }?,
    ],
): Promise<void> => {
    try {
        if (isFile(item)) {
            await deleteFile(item);
        } else if (isLink(item)) {
            await deleteLink(item);
        } else if (isEmbedded(item)) {
            await deleteEmbeddedFile(item);
        } else if (isWiki(item) && additional_wiki_options) {
            await deleteWiki(item, additional_wiki_options);
        } else if (isEmpty(item)) {
            await deleteEmptyDocument(item);
        } else if (isFolder(item)) {
            await deleteFolder(item);
        }

        emitter.emit("item-has-just-been-deleted");

        context.commit("clipboard/emptyClipboardAfterItemDeletion", item);
        context.commit("removeItemFromFolderContent", item);
        context.commit("showPostDeletionNotification");

        return Promise.resolve();
    } catch (exception) {
        return handleErrorsForDeletionModal(context, exception, item);
    }
};

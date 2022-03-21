/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    moveEmbedded,
    moveEmpty,
    moveFolder,
    moveLink,
    moveWiki,
    moveFile,
    copyEmbedded,
    copyEmpty,
    copyFolder,
    copyLink,
    copyWiki,
    copyFile,
} from "../../api/move-rest-querier";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
    CLIPBOARD_OPERATION_CUT,
    CLIPBOARD_OPERATION_COPY,
} from "../../constants";
import { adjustItemToContentAfterItemCreationInAFolder } from "../actions-helpers/adjust-item-to-content-after-item-creation-in-folder";
import type { Folder, Item, State } from "../../type";
import type { ActionContext } from "vuex";
import type { ClipboardState } from "./module";
import emitter from "../../helpers/emitter";

export interface PastePayload {
    destination_folder: Folder;
    current_folder: Folder;
    global_context: ActionContext<State, State>;
}
export const pasteItem = async (
    context: ActionContext<ClipboardState, State>,
    payload: PastePayload
): Promise<void> => {
    if (context.state.pasting_in_progress) {
        return;
    }
    context.commit("startPasting");
    try {
        let pasted_item_id;
        switch (context.state.operation_type) {
            case CLIPBOARD_OPERATION_CUT:
                await pasteItemBeingMoved(context, payload.destination_folder);
                pasted_item_id = context.state.item_id;
                break;
            case CLIPBOARD_OPERATION_COPY:
                pasted_item_id = (await pasteItemBeingCopied(context, payload.destination_folder))
                    .id;
                break;
            default:
                context.commit("emptyClipboard");
                throw new Error(
                    "Cannot paste from an unknown operation " + context.state.item_type
                );
        }
        context.commit("emptyClipboard");
        emitter.emit("new-item-has-just-been-created");
        if (!pasted_item_id) {
            throw new Error("Paste item id is unknown");
        }
        adjustItemToContentAfterItemCreationInAFolder(
            payload.global_context,
            payload.destination_folder,
            payload.current_folder,
            pasted_item_id
        );
    } catch (exception) {
        context.commit("pastingHasFailed");
        await context.dispatch("error/handleGlobalModalError", exception, { root: true });
    }
};

function pasteItemBeingMoved(
    context: ActionContext<ClipboardState, State>,
    destination_folder: Folder
): Promise<void> {
    if (!context.state.item_id) {
        throw new Error("Cannot copy unknown item");
    }
    switch (context.state.item_type) {
        case TYPE_FILE:
            return moveFile(context.state.item_id, destination_folder.id);
        case TYPE_FOLDER:
            return moveFolder(context.state.item_id, destination_folder.id);
        case TYPE_EMPTY:
            return moveEmpty(context.state.item_id, destination_folder.id);
        case TYPE_WIKI:
            return moveWiki(context.state.item_id, destination_folder.id);
        case TYPE_EMBEDDED:
            return moveEmbedded(context.state.item_id, destination_folder.id);
        case TYPE_LINK:
            return moveLink(context.state.item_id, destination_folder.id);
        default:
            context.commit("emptyClipboard");
            throw new Error("Cannot copy unknown item type " + context.state.item_type);
    }
}

function pasteItemBeingCopied(
    context: ActionContext<ClipboardState, State>,
    destination_folder: Folder
): Promise<Item> {
    if (!context.state.item_id) {
        throw new Error("Cannot copy unknown item");
    }
    switch (context.state.item_type) {
        case TYPE_FILE:
            return copyFile(context.state.item_id, destination_folder.id);
        case TYPE_FOLDER:
            return copyFolder(context.state.item_id, destination_folder.id);
        case TYPE_EMPTY:
            return copyEmpty(context.state.item_id, destination_folder.id);
        case TYPE_WIKI:
            return copyWiki(context.state.item_id, destination_folder.id);
        case TYPE_EMBEDDED:
            return copyEmbedded(context.state.item_id, destination_folder.id);
        case TYPE_LINK:
            return copyLink(context.state.item_id, destination_folder.id);
        default:
            context.commit("emptyClipboard");
            throw new Error("Cannot copy unknown item type " + context.state.item_type);
    }
}

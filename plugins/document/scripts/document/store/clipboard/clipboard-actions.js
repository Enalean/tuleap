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
    copyEmbedded,
    copyEmpty,
    copyFolder,
    copyLink,
    copyWiki,
    copyFile
} from "../../api/rest-querier.js";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI
} from "../../constants.js";
import { adjustItemToContentAfterItemCreation } from "../actions-helpers/adjust-item-to-content-after-item-creation.js";
import { handleErrors } from "../actions-helpers/handle-errors.js";

export const pasteItem = async (context, [destination_folder, current_folder, global_context]) => {
    if (context.state.pasting_in_progress) {
        return;
    }
    context.commit("startPasting");
    try {
        let item_reference;
        switch (context.state.item_type) {
            case TYPE_FILE:
                item_reference = await copyFile(context.state.item_id, destination_folder.id);
                break;
            case TYPE_FOLDER:
                item_reference = await copyFolder(context.state.item_id, destination_folder.id);
                break;
            case TYPE_EMPTY:
                item_reference = await copyEmpty(context.state.item_id, destination_folder.id);
                break;
            case TYPE_WIKI:
                item_reference = await copyWiki(context.state.item_id, destination_folder.id);
                break;
            case TYPE_EMBEDDED:
                item_reference = await copyEmbedded(context.state.item_id, destination_folder.id);
                break;
            case TYPE_LINK:
                item_reference = await copyLink(context.state.item_id, destination_folder.id);
                break;
            default:
                context.commit("emptyClipboard");
                throw new Error("Cannot copy unknown item type " + context.state.item_type);
        }
        context.commit("emptyClipboard");
        return adjustItemToContentAfterItemCreation(
            global_context,
            destination_folder,
            current_folder,
            item_reference.id
        );
    } catch (exception) {
        context.commit("pastingHasFailed");
        return handleErrors(global_context, exception);
    }
};

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

import defaultState from "./clipboard-default-state.js";
import { CLIPBOARD_OPERATION_CUT, CLIPBOARD_OPERATION_COPY } from "../../constants.js";

export {
    cutItem,
    copyItem,
    emptyClipboardAfterItemDeletion,
    emptyClipboard,
    startPasting,
    pastingHasFailed,
};

function cutItem(state, item) {
    startNewClipboardOperation(state, item, CLIPBOARD_OPERATION_CUT);
}

function copyItem(state, item) {
    startNewClipboardOperation(state, item, CLIPBOARD_OPERATION_COPY);
}

function startNewClipboardOperation(state, item, operationType) {
    if (state.pasting_in_progress) {
        return;
    }
    state.item_id = item.id;
    state.item_type = item.type;
    state.item_title = item.title;
    state.operation_type = operationType;
}

function emptyClipboardAfterItemDeletion(state, deleted_item) {
    if (state.item_id === deleted_item.id) {
        emptyClipboard(state);
    }
}

function emptyClipboard(state) {
    Object.assign(state, defaultState());
}

function startPasting(state) {
    state.pasting_in_progress = true;
}

function pastingHasFailed(state) {
    state.pasting_in_progress = false;
}

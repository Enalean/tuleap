/*
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

import type { Ref } from "vue";
import type { PastePayload } from "./clipboard";
import type { Folder, Item } from "../type";
import type { Store } from "pinia";

export interface ClipboardState {
    item_id: Ref<null | number>;
    move_uri: Ref<null | string>;
    item_title: Ref<null | string>;
    item_type: Ref<null | string>;
    operation_type: Ref<null | string>;
    pasting_in_progress: Ref<boolean>;
}

export interface ClipboardGetters {}

export interface ClipboardActions {
    pasteItem(payload: PastePayload): Promise<void>;
    pasteItemBeingMoved(destination_folder: Folder): Promise<void>;
    pasteItemBeingCopied(destination_folder: Folder): Promise<Item>;
    cutItem(item: Item): void;
    copyItem(item: Item): void;
    startNewClipboardOperation(item: Item, operationType: string): void;
    emptyClipboardAfterItemDeletion(deleted_item: Item): void;
    emptyClipboard(): void;
    startPasting(): void;
    pastingHasFailed(): void;
}

export type ClipboardStore = Store<"clipboard", ClipboardState, ClipboardGetters, ClipboardActions>;

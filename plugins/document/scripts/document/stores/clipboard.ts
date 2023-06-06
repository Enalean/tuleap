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

import type { Pinia } from "pinia";
import { defineStore } from "pinia";
import type { ClipboardState } from "./types";
import type { Folder, RootState, Item } from "../type";
import {
    CLIPBOARD_OPERATION_COPY,
    CLIPBOARD_OPERATION_CUT,
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../constants";
import emitter from "../helpers/emitter";
import {
    copyEmbedded,
    copyEmpty,
    copyFile,
    copyFolder,
    copyLink,
    copyWiki,
    moveEmbedded,
    moveEmpty,
    moveFile,
    moveFolder,
    moveLink,
    moveWiki,
} from "../api/move-rest-querier";
import { useLocalStorage } from "@vueuse/core";
import type { Store } from "vuex";

export interface PastePayload {
    destination_folder: Folder;
    current_folder: Folder;
}

function buildBaseStorageKey(project_id: string, user_id: string): string {
    return `document_clipboard_state_${project_id}_${user_id}_`;
}

// We are forced to rewrap defineStore() to make sure the shared items are properly scoped. Letting the type inference
// retrieving the return type from defineStore() is fine here, hardcoding it will only bring us more work in the long
// run for no advantages.
// eslint-disable-next-line @typescript-eslint/explicit-function-return-type
export function useClipboardStore(
    store: Store<RootState>,
    project_id: string,
    user_id: string,
    pinia?: Pinia | null | undefined
) {
    const base_storage_key = buildBaseStorageKey(project_id, user_id);
    return defineStore("clipboard", {
        state: (): ClipboardState => ({
            item_id: useLocalStorage(`${base_storage_key}item_id`, null),
            item_title: useLocalStorage(`${base_storage_key}item_title`, null),
            item_type: useLocalStorage(`${base_storage_key}item_type`, null),
            operation_type: useLocalStorage(`${base_storage_key}operation_type`, null),
            pasting_in_progress: useLocalStorage(`${base_storage_key}pasting_in_progress`, false),
        }),
        actions: {
            async pasteItem(payload: PastePayload): Promise<void> {
                if (this.pasting_in_progress) {
                    return Promise.resolve();
                }
                this.startPasting();
                try {
                    let pasted_item_id;
                    switch (this.operation_type) {
                        case CLIPBOARD_OPERATION_CUT:
                            await this.pasteItemBeingMoved(payload.destination_folder);
                            pasted_item_id = this.item_id;
                            break;
                        case CLIPBOARD_OPERATION_COPY:
                            pasted_item_id = (
                                await this.pasteItemBeingCopied(payload.destination_folder)
                            ).id;
                            break;
                        default:
                            this.emptyClipboard();
                            throw new Error(
                                "Cannot paste from an unknown operation " + this.operation_type
                            );
                    }
                    this.emptyClipboard();
                    if (!pasted_item_id) {
                        throw new Error("Paste item id is unknown");
                    }
                    emitter.emit("new-item-has-just-been-created", { id: pasted_item_id });

                    await store.dispatch(
                        "adjustItemToContentAfterItemCreationInAFolder",
                        {
                            parent: payload.destination_folder,
                            current_folder: payload.current_folder,
                            item_id: pasted_item_id,
                        },
                        { root: true }
                    );
                } catch (exception) {
                    this.pastingHasFailed();
                    await store.dispatch("error/handleGlobalModalError", exception, { root: true });
                }

                return Promise.resolve();
            },
            pasteItemBeingMoved(destination_folder: Folder): Promise<void> {
                if (!this.item_id) {
                    throw new Error("Cannot copy unknown item");
                }
                switch (this.item_type) {
                    case TYPE_FILE:
                        return moveFile(this.item_id, destination_folder.id);
                    case TYPE_FOLDER:
                        return moveFolder(this.item_id, destination_folder.id);
                    case TYPE_EMPTY:
                        return moveEmpty(this.item_id, destination_folder.id);
                    case TYPE_WIKI:
                        return moveWiki(this.item_id, destination_folder.id);
                    case TYPE_EMBEDDED:
                        return moveEmbedded(this.item_id, destination_folder.id);
                    case TYPE_LINK:
                        return moveLink(this.item_id, destination_folder.id);
                    default:
                        this.emptyClipboard();
                        throw new Error("Cannot copy unknown item type " + this.item_type);
                }
            },
            pasteItemBeingCopied(destination_folder: Folder): Promise<Item> {
                if (!this.item_id) {
                    throw new Error("Cannot copy unknown item");
                }
                switch (this.item_type) {
                    case TYPE_FILE:
                        return copyFile(this.item_id, destination_folder.id);
                    case TYPE_FOLDER:
                        return copyFolder(this.item_id, destination_folder.id);
                    case TYPE_EMPTY:
                        return copyEmpty(this.item_id, destination_folder.id);
                    case TYPE_WIKI:
                        return copyWiki(this.item_id, destination_folder.id);
                    case TYPE_EMBEDDED:
                        return copyEmbedded(this.item_id, destination_folder.id);
                    case TYPE_LINK:
                        return copyLink(this.item_id, destination_folder.id);
                    default:
                        this.emptyClipboard();
                        throw new Error("Cannot copy unknown item type " + this.item_type);
                }
            },

            cutItem(item: Item): void {
                this.startNewClipboardOperation(item, CLIPBOARD_OPERATION_CUT);
            },

            copyItem(item: Item): void {
                this.startNewClipboardOperation(item, CLIPBOARD_OPERATION_COPY);
            },

            startNewClipboardOperation(item: Item, operationType: string): void {
                if (this.pasting_in_progress) {
                    return;
                }
                this.item_id = item.id;
                this.item_type = item.type;
                this.item_title = item.title;
                this.operation_type = operationType;
            },

            emptyClipboardAfterItemDeletion(deleted_item: Item): void {
                if (this.item_id === deleted_item.id) {
                    this.emptyClipboard();
                }
            },

            emptyClipboard(): void {
                this.item_id = null;
                this.item_title = null;
                this.item_type = null;
                this.operation_type = null;
                this.pasting_in_progress = false;
            },

            startPasting(): void {
                this.pasting_in_progress = true;
            },

            pastingHasFailed(): void {
                this.pasting_in_progress = false;
            },
        },
    })(pinia);
}

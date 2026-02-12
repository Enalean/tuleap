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
    findNearestByTitle,
    getDocumentSiblings,
    getFolderSiblings,
    getLastOrThrow,
    insertAfterItem,
    insertAfterLastInSubtree,
    insertBeforeItem,
} from "../helpers/folder/folder-content-filter";
import { getFolderSubtree } from "../helpers/retrieve-subtree-helper";
import { isFolder } from "../helpers/type-check-helper";
import type { FakeItem, Folder, FolderContentItem, Item, State } from "../type";
import { toRaw } from "vue";

export function saveFolderContent(state: State, folder_content: FolderContentItem[]): void {
    state.folder_content = folder_content;
}

function addDocumentToTheRightPlace(
    state: State,
    new_item: FolderContentItem,
    document_siblings: FolderContentItem[],
    folder_siblings: FolderContentItem[],
    parent: FolderContentItem,
): void {
    // Given item is a document, and given other documents already exist in the folder
    // Then we should respect document insertion order
    if (document_siblings.length > 0) {
        const alphabetical_sibling = findNearestByTitle(document_siblings, new_item);

        // Given document has siblings, insert it alphabetically
        if (alphabetical_sibling) {
            insertBeforeItem(state, alphabetical_sibling, new_item);
            return;
        }

        // Otherwise insert after last file
        insertAfterLastInSubtree(state, getLastOrThrow(document_siblings), new_item);
        return;
    }

    // Given item is a document, and no other folder already exist in the folder
    // Then we should insert it after last folder subtree
    if (folder_siblings.length > 0) {
        insertAfterLastInSubtree(state, getLastOrThrow(folder_siblings), new_item);
        return;
    }

    // Given folder is empty
    // Then we should insert it after parent
    insertAfterItem(state, parent, new_item);
}

function addFolderToTheRightPlace(
    state: State,
    new_item: FolderContentItem,
    parent: FolderContentItem,
    folder_siblings: FolderContentItem[],
    document_siblings: FolderContentItem[],
): void {
    // Given folder has subfolders elements
    if (folder_siblings.length > 0) {
        const alphabetical_sibling = findNearestByTitle(folder_siblings, new_item);

        // and given we find alphabetical insertion point => insert it alphabetically
        if (alphabetical_sibling) {
            insertBeforeItem(state, alphabetical_sibling, new_item);
            return;
        }

        // Otherwise insert after last file
        insertAfterLastInSubtree(state, getLastOrThrow(folder_siblings), new_item);
        return;
    }

    // Given folder has NO folder BUT have documents elements => insert it before the first document
    if (document_siblings.length > 0) {
        insertBeforeItem(state, document_siblings[0], new_item);
        return;
    }

    // Given folder is empty =>  Insert it directly after its parent
    insertAfterItem(state, parent, new_item);
}

export interface AdjustItemToFolderContentPayload {
    parent: Folder;
    new_item: FolderContentItem;
}

export function addJustCreatedItemToFolderContent(
    state: State,
    payload: AdjustItemToFolderContentPayload,
): void {
    const new_item = payload.new_item;
    const parent = payload.parent;
    const folder_siblings = getFolderSiblings(state, new_item.parent_id);
    const document_siblings = getDocumentSiblings(state, new_item.parent_id);

    new_item.level = parent.level !== undefined ? parent.level + 1 : 0;

    // Given item is a folder
    // Then we should respect folder insertion order
    if (isFolder(new_item)) {
        addFolderToTheRightPlace(state, new_item, parent, folder_siblings, document_siblings);
        return;
    }

    addDocumentToTheRightPlace(state, new_item, document_siblings, folder_siblings, parent);
}

export function appendSubFolderContent(
    state: State,
    [folder_id, sub_items]: [number, FolderContentItem[]],
): void {
    const folder_index = state.folder_content.findIndex((folder) => folder.id === folder_id);
    const parent_folder = state.folder_content[folder_index];
    if (!parent_folder) {
        return;
    }

    if (parent_folder.level === undefined) {
        parent_folder.level = 0;
    }

    sub_items.forEach((item) => {
        if (parent_folder.level !== undefined) {
            item.level = parent_folder.level + 1;
        }
    });

    const filtered_sub_items = sub_items.filter(
        (item) =>
            state.folder_content.findIndex((existing_item) => item.id === existing_item.id) === -1,
    );

    state.folder_content.splice(folder_index + 1, 0, ...filtered_sub_items);

    const children_ids = filtered_sub_items.map((item) => item.id);

    if (isParentFoldedByOnOfIsAncestors(state, parent_folder)) {
        state.folded_items_ids.push(...children_ids);

        const folder = findAncestorFoldingFolder(state, folder_id);

        if (folder !== undefined) {
            state.folded_by_map[Number.parseInt(folder, 10)].push(...children_ids);
        }
    }
}

function findAncestorFoldingFolder(state: State, folder_id: number): string | undefined {
    return Object.keys(state.folded_by_map).find((key: string) =>
        state.folded_by_map[Number.parseInt(key, 10)].includes(folder_id),
    );
}

function isParentFoldedByOnOfIsAncestors(state: State, parent_folder: FolderContentItem): boolean {
    return (
        state.folded_items_ids.find(
            (folded_item_id: number) => folded_item_id === parent_folder.id,
        ) !== undefined
    );
}

export function foldFolderContent(state: State, folder_id: number): void {
    const index = state.folder_content.findIndex((item) => item.id === folder_id);

    if (index !== -1) {
        const item = structuredClone(toRaw(state.folder_content[index]));
        if (isFolder(item)) {
            item.is_expanded = false;
            state.folder_content[index] = item;
        }
    }
    const children = getFolderUnfoldedDescendants(state, folder_id);
    const folded_content = children.map((item) => item.id);

    state.folded_items_ids = state.folded_items_ids.concat(folded_content);

    state.folded_by_map[folder_id] = folded_content;
}

export function addDocumentToFoldedFolder(
    state: State,
    [parent, item, should_display_fake_item]: [FolderContentItem, FolderContentItem, boolean],
): void {
    if (!should_display_fake_item) {
        if (!state.folded_by_map[parent.id]) {
            state.folded_by_map[parent.id] = [];
        }

        state.folded_by_map[parent.id].push(item.id);
        state.folded_items_ids.push(item.id);
    }
}

export function unfoldFolderContent(state: State, folder_id: number): void {
    const index = state.folder_content.findIndex((item) => item.id === folder_id);

    if (index !== -1) {
        const item = structuredClone(toRaw(state.folder_content[index]));
        if (isFolder(item)) {
            item.is_expanded = true;
            state.folder_content[index] = item;
        }
    }

    const items_to_unfold = state.folded_by_map[folder_id];

    if (!items_to_unfold) {
        return;
    }

    state.folded_items_ids = state.folded_items_ids.filter(
        (item: number) => !items_to_unfold.includes(item),
    );

    delete state.folded_by_map[folder_id];
}

export function resetFoldedLists(state: State): void {
    state.folded_items_ids = [];
    state.folded_by_map = {};
}

function getFolderUnfoldedDescendants(state: State, folder_id: number): FolderContentItem[] {
    const children = state.folder_content.filter((item) => item.parent_id === folder_id);

    const unfolded_descendants: FolderContentItem[] = [];

    children.forEach((child) => {
        if (Object.prototype.hasOwnProperty.call(state.folded_by_map, child.id)) {
            return;
        }

        unfolded_descendants.push(...getFolderUnfoldedDescendants(state, child.id));
    });

    return children.concat(unfolded_descendants);
}

export function removeCreatedPropertyOnItem(state: State, item: Item): void {
    delete item.created;
}

export function replaceUploadingFileWithActualFile(
    state: State,
    [uploading_file, actual_file]: [FakeItem, Item],
): void {
    const currently_previewed_item = state.currently_previewed_item;
    if (currently_previewed_item?.id === uploading_file.id) {
        state.currently_previewed_item = actual_file;
    }

    const index = state.folder_content.findIndex((item) => item.id === uploading_file.id);
    if (index === -1) {
        return;
    }

    state.folder_content.splice(index, 1, actual_file);
}

export function removeItemFromFolderContent(state: State, item_to_remove: FolderContentItem): void {
    const index = state.folder_content.findIndex((item) => item.id === item_to_remove.id);
    if (index === -1) {
        return;
    }

    if (isFolder(item_to_remove)) {
        unfoldFolderContent(state, item_to_remove.id);

        const children = getFolderSubtree(state.folder_content, item_to_remove.id);

        children.forEach((child) => {
            if (isFolder(child)) {
                unfoldFolderContent(state, child.id);
            }

            removeItemFromFolderContent(state, child);
        });
    }

    state.folder_content.splice(index, 1);
}

export function updateCurrentItemForQuickLokDisplay(state: State, item: FolderContentItem): void {
    state.currently_previewed_item = item;
}

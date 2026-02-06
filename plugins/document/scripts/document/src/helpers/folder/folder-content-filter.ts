/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

import type { FakeItem, FolderContentItem, Item, State } from "../../type";
import { isFolder } from "../type-check-helper";

export function findNearestByTitle(
    items: Array<FolderContentItem>,
    new_item: FolderContentItem,
): FolderContentItem | undefined {
    return items.find((sibling) => {
        return (
            sibling.title.localeCompare(new_item.title, undefined, {
                numeric: true,
            }) >= 0
        );
    });
}

export function getDocumentSiblings(
    state: State,
    parent_id: number | null,
): Array<FolderContentItem> {
    return getSiblings(state, parent_id).filter((item) => !isFolder(item));
}

export function getFolderSiblings(
    state: State,
    parent_id: number | null,
): Array<FolderContentItem> {
    return getSiblings(state, parent_id).filter(isFolder);
}

function checkWeAreInSubTreeWhenLevelIsUpperThanBaseLevel(
    current: Item | FakeItem,
    folder_level: number,
): boolean {
    return current.level === undefined ? false : current.level > folder_level;
}

export function getLastItemInSubtree(state: State, folder: FolderContentItem): FolderContentItem {
    const start_index = state.folder_content.findIndex((item) => item.id === folder.id);
    if (start_index === -1) {
        return folder;
    }

    const folder_level = folder.level ? folder.level : 0;
    let last_item = folder;

    for (let i = start_index + 1; i < state.folder_content.length; i++) {
        const current = state.folder_content[i];

        if (checkWeAreInSubTreeWhenLevelIsUpperThanBaseLevel(current, folder_level)) {
            last_item = current;
        } else {
            break;
        }
    }

    return last_item;
}

export function insertBeforeItem(
    state: State,
    target: FolderContentItem,
    item: FolderContentItem,
): void {
    insertAtIndex(state, indexOf(state, target), item);
}

export function insertAfterItem(
    state: State,
    target: FolderContentItem,
    item: FolderContentItem,
): void {
    insertAtIndex(state, indexOf(state, target) + 1, item);
}

export function getLastOrThrow(items: Array<FolderContentItem>): FolderContentItem {
    const last = items.at(-1);
    if (last === undefined) {
        throw new Error("Expected a non-empty array");
    }
    return last;
}

export function insertAfterLastInSubtree(
    state: State,
    folder: FolderContentItem,
    new_item: FolderContentItem,
): void {
    const last = getLastItemInSubtree(state, folder);
    insertAfterItem(state, last, new_item);
}

function indexOf(state: State, item: FolderContentItem): number {
    return state.folder_content.findIndex((i) => i.id === item.id);
}

function insertAtIndex(state: State, index: number, item: FolderContentItem): void {
    state.folder_content.splice(index, 0, item);
}

function getSiblings(state: State, parent_id: number | null): Array<FolderContentItem> {
    return state.folder_content.filter((item) => item.parent_id === parent_id);
}

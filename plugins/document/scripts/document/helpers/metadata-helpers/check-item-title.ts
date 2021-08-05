/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import type { Folder, Item } from "../../type";
import { isFolder } from "../type-check-helper";

function doesTitleAlreadyExists(text_value: string, item: Item, parent_folder: Folder): boolean {
    return item.title === text_value && item.parent_id === parent_folder.id;
}

export function doesFolderNameAlreadyExist(
    text_value: string,
    folder_content: Array<Item>,
    parent_folder: Folder
): boolean {
    return folder_content.some((item: Item) => {
        return doesTitleAlreadyExists(text_value, item, parent_folder) && isFolder(item);
    });
}

export function doesDocumentNameAlreadyExist(
    text_value: string,
    folder_content: Array<Item>,
    parent_folder: Folder
): boolean {
    return folder_content.some((item) => {
        return doesTitleAlreadyExists(text_value, item, parent_folder) && !isFolder(item);
    });
}

export function doesDocumentAlreadyExistsAtUpdate(
    text_value: string,
    folder_content: Array<Item>,
    item_to_update: Item,
    parent_folder: Folder
): boolean {
    return folder_content.some((item) => {
        return (
            doesTitleAlreadyExists(text_value, item, parent_folder) &&
            !isFolder(item) &&
            item.id !== item_to_update.id
        );
    });
}

export function doesFolderAlreadyExistsAtUpdate(
    text_value: string,
    folder_content: Array<Item>,
    item_to_update: Item,
    parent_folder: Folder
): boolean {
    return folder_content.some((item) => {
        return (
            doesTitleAlreadyExists(text_value, item, parent_folder) &&
            isFolder(item) &&
            item.id !== item_to_update.id
        );
    });
}

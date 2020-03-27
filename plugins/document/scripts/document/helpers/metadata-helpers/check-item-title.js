/*
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

import { TYPE_FOLDER } from "../../constants.js";

function doesTitleAlreadyExists(text_value, item, parent_folder) {
    return item.title === text_value && item.parent_id === parent_folder.id;
}

export function doesFolderNameAlreadyExist(text_value, folder_content, parent_folder) {
    return folder_content.some((item) => {
        return doesTitleAlreadyExists(text_value, item, parent_folder) && item.type === TYPE_FOLDER;
    });
}

export function doesDocumentNameAlreadyExist(text_value, folder_content, parent_folder) {
    return folder_content.some((item) => {
        return doesTitleAlreadyExists(text_value, item, parent_folder) && item.type !== TYPE_FOLDER;
    });
}

export function doesDocumentAlreadyExistsAtUpdate(
    text_value,
    folder_content,
    item_to_update,
    parent_folder
) {
    return folder_content.some((item) => {
        return (
            doesTitleAlreadyExists(text_value, item, parent_folder) &&
            item.type !== TYPE_FOLDER &&
            item.id !== item_to_update.id
        );
    });
}

export function doesFolderAlreadyExistsAtUpdate(
    text_value,
    folder_content,
    item_to_update,
    parent_folder
) {
    return folder_content.some((item) => {
        return (
            doesTitleAlreadyExists(text_value, item, parent_folder) &&
            item.type === TYPE_FOLDER &&
            item.id !== item_to_update.id
        );
    });
}

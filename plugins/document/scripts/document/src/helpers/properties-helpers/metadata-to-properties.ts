/*
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
 *
 */

import type { Folder, Item, Uploadable } from "../../type";
import type { RestFolder, RestItem } from "../../api/rest-querier";
import { isFile, isFolder } from "../type-check-helper";

export function convertArrayOfItems(items: ReadonlyArray<RestItem>): Array<Item> {
    return items.map(({ metadata, ...other }) =>
        addUploadableProperties({
            properties: metadata,
            ...other,
        }),
    );
}

export function convertRestItemToItem(rest_item: RestItem): Item {
    return addUploadableProperties({ properties: rest_item.metadata, ...rest_item });
}

export function convertFolderItemToFolder(rest_folder: RestFolder): Folder {
    return addUploadableProperties({ properties: rest_folder.metadata, ...rest_folder });
}

function addUploadableProperties<I extends Item>(item: I): I {
    if (isFolder(item) || isFile(item)) {
        const default_uploadable_value: Uploadable = {
            progress: null,
            upload_error: null,
            is_uploading: false,
            is_uploading_new_version: false,
            is_uploading_in_collapsed_folder: false,
        };
        return {
            ...item,
            ...default_uploadable_value,
        };
    }

    return item;
}

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

import type { Folder, Item } from "../../type";
import type { RestFolder, RestItem } from "../../api/rest-querier";

export function convertArrayOfItems(items: ReadonlyArray<RestItem>): Array<Item> {
    return items.map(({ metadata, ...other }) => ({
        properties: metadata,
        ...other,
    }));
}

export function convertRestItemToItem(rest_item: RestItem): Item {
    return { properties: rest_item.metadata, ...rest_item };
}

export function convertFolderItemToFolder(rest_folder: RestFolder): Folder {
    return { properties: rest_folder.metadata, ...rest_folder };
}

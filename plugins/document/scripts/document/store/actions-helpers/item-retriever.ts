/*
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

import type { FakeItem, Folder, Item } from "../../type";
import { isFolder } from "../../helpers/type-check-helper";

export function getParentFolder(
    folder_content: Array<Item | FakeItem>,
    item: Item | FakeItem,
    current_folder: Folder,
): Folder {
    const found_parent_folder = folder_content.find(
        (possible_parent) => possible_parent.id === item.parent_id,
    );
    if (found_parent_folder && !isFolder(found_parent_folder)) {
        throw new Error(
            "Parent item " + found_parent_folder.id + " of item " + item.id + "is not a folder",
        );
    }

    if (!found_parent_folder) {
        return current_folder;
    }
    return found_parent_folder;
}

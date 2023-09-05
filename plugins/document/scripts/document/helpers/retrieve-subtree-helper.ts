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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Item } from "../type";
import { isFolder } from "./type-check-helper";

export function getFolderSubtree(
    folder_content: Array<Item>,
    subtree_root_folder_id: number,
): Array<Item> {
    const children = folder_content.filter((item) => item.parent_id === subtree_root_folder_id);

    const undirect_children: Array<Item> = [];

    children.forEach((child) => {
        if (isFolder(child)) {
            undirect_children.push(...getFolderSubtree(folder_content, child.id));
        }
    });

    return children.concat(undirect_children);
}

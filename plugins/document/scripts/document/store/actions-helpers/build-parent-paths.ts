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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Item, ItemReferencingWikiPageRepresentation } from "../../type";

export interface ItemPath {
    id: number;
    path: string;
}
export function buildItemPath(
    item: ItemReferencingWikiPageRepresentation,
    parents: Array<Item>,
): ItemPath {
    const path = parents.reduce((path, parent) => path + `/${parent.title}`, "");

    return {
        path: path + `/${item.item_name}`,
        id: item.item_id,
    };
}

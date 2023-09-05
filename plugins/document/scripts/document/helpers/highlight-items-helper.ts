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
import { isFile, isFolder } from "./type-check-helper";

export const highlightItem = (item: Item, closest_row: HTMLElement): void => {
    if (item.user_can_write) {
        applyDefaultClass(closest_row);
        applyIconClass(item, closest_row);
    } else {
        applyHighlightForbiddenClass(closest_row);
    }
};

function applyDefaultClass(closest_row: HTMLElement): void {
    if (closest_row.classList.contains("document-quick-look-pane")) {
        closest_row.classList.add("quick-look-pane-highlighted");
    } else {
        closest_row.classList.add("document-tree-item-highlighted");
    }
}

function applyIconClass(item: Item, closest_row: HTMLElement): void {
    if (isFile(item)) {
        closest_row.classList.add("document-file-highlighted");
    } else if (isFolder(item)) {
        closest_row.classList.add("document-folder-highlighted");
    }
}

function applyHighlightForbiddenClass(closest_row: HTMLElement): void {
    if (closest_row.classList.contains("document-quick-look-pane")) {
        closest_row.classList.add("quick-look-pane-highlighted-forbidden");
    } else {
        closest_row.classList.add(
            "document-tree-item-highlighted",
            "document-tree-item-hightlighted-forbidden",
        );
    }
}

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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import type { FolderContentItem } from "../../type";

export function isItemDestinationIntoItself(
    folder_content: Array<FolderContentItem>,
    item_id: number,
    destination_id: number | null,
): boolean {
    if (destination_id === item_id) {
        return true;
    }

    const destination_item = folder_content.find(({ id }) => id === destination_id);
    if (!destination_item || destination_item.id === 0) {
        return false;
    }

    return isItemDestinationIntoItself(folder_content, item_id, destination_item.parent_id);
}

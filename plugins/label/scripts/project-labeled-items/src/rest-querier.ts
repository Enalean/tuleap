/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

import { get } from "@tuleap/tlp-fetch";
import type { Item } from "./type";

interface LabeledItemsResponse {
    readonly labeled_items: ReadonlyArray<Item>;
    readonly has_more: boolean;
    readonly offset: number;
    readonly are_there_items_user_cannot_see: boolean;
}

export async function getLabeledItems(
    project_id: string,
    labels_id: Array<number>,
    offset: number,
    limit: number,
): Promise<LabeledItemsResponse> {
    const response = await get(
        "/api/projects/" + encodeURIComponent(project_id) + "/labeled_items",
        {
            params: {
                query: JSON.stringify({ labels_id }),
                limit,
                offset,
            },
        },
    );

    const pagination_size = response.headers.get("X-PAGINATION-SIZE");
    if (pagination_size === null) {
        throw new Error("No X-PAGINATION-SIZE field in the header.");
    }
    const total = Number.parseInt(pagination_size, 10);
    const json = await response.json();

    json.has_more = limit + offset < total;
    json.offset = offset;

    if (json.has_more && json.are_there_items_user_cannot_see && json.labeled_items.length === 0) {
        return getLabeledItems(project_id, labels_id, offset + limit, limit);
    }

    return json;
}

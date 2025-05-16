/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { getItem } from "../api/rest-querier";
import type { ActionContext } from "vuex";
import type { Embedded, Item, Link, RootState, Wiki } from "../type";

export const refreshLink = async (
    context: ActionContext<RootState, RootState>,
    item_to_refresh: Link,
): Promise<void> => {
    const up_to_date_item = await getItem(item_to_refresh.id);

    context.commit("replaceFolderContentByItem", up_to_date_item, { root: true });
};

export const refreshWiki = async (
    context: ActionContext<RootState, RootState>,
    item_to_refresh: Wiki,
): Promise<void> => {
    const up_to_date_item = await getItem(item_to_refresh.id);

    context.commit("replaceFolderContentByItem", up_to_date_item, { root: true });
};

export const refreshEmbeddedFile = async (
    context: ActionContext<RootState, RootState>,
    item_to_refresh: Embedded,
): Promise<Item> => {
    const up_to_date_item = await getItem(item_to_refresh.id);

    context.commit("replaceFolderContentByItem", up_to_date_item, { root: true });

    return up_to_date_item;
};

/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { LazyboxTemplatingCallback, RenderedItem, RenderedItemMap } from "../type";
import { getRenderedListItem } from "../renderers/DropdownContentRenderer";
import type { GroupCollection, LazyboxItem } from "./GroupCollection";
import { getGroupId } from "../helpers/group-id-helper";
import { html } from "lit/html.js";

const getItemId = (item: LazyboxItem, group_id: string): string => {
    let base_id = "lazybox-item-";

    if (group_id !== "") {
        base_id += group_id + "-";
    }

    return base_id + item.id;
};

export interface ListItemMapBuilderType {
    buildLazyboxItemsMap(groups: GroupCollection): RenderedItemMap;
    buildRenderedItem(item: LazyboxItem, group_id: string): RenderedItem;
}

export const ListItemMapBuilder = (
    templating_callback: LazyboxTemplatingCallback
): ListItemMapBuilderType => {
    const buildRenderedItem = (item: LazyboxItem, group_id: string): RenderedItem => {
        const id = getItemId(item, group_id);
        const template = templating_callback(html, item);
        return {
            id,
            group_id,
            is_disabled: item.is_disabled,
            is_selected: false,
            value: item.value,
            template: template,
            element: getRenderedListItem(id, template, item.is_disabled),
        };
    };

    const addItemInMap = (
        group_id: string,
        item: LazyboxItem,
        accumulator: RenderedItemMap
    ): RenderedItemMap => {
        const rendered_item = buildRenderedItem(item, group_id);
        accumulator.set(rendered_item.id, rendered_item);
        return accumulator;
    };

    return {
        buildLazyboxItemsMap(groups: GroupCollection): RenderedItemMap {
            return groups.reduce((accumulator: RenderedItemMap, group) => {
                const group_id = getGroupId(group);
                return group.items.reduce(
                    (inner_accumulator: RenderedItemMap, item) =>
                        addItemInMap(group_id, item, inner_accumulator),
                    accumulator
                );
            }, new Map());
        },
        buildRenderedItem,
    };
};

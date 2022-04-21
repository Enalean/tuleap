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

import type { RenderedItem, RenderedItemMap } from "../type";
import { getRenderedListItem } from "../renderers/DropdownContentRenderer";
import type { GroupCollection, LinkSelectorItem } from "./GroupCollection";
import { getGroupId } from "../helpers/group-id-helper";

const getItemId = (value: string, group_id: string): string => {
    let base_id = "link-selector-item-";

    if (group_id !== "") {
        base_id += group_id + "-";
    }

    const option_value = value.includes(" ") ? value.split(" ").join("-") : value;

    return base_id + option_value;
};

const buildOption = (doc: Document, item: LinkSelectorItem, id: string): HTMLOptionElement => {
    const option = doc.createElement("option");
    option.value = item.value;
    option.setAttribute("data-item-id", id);
    return option;
};

const addItemInMap = (
    doc: Document,
    group_id: string,
    item: LinkSelectorItem,
    accumulator: RenderedItemMap
): RenderedItemMap => {
    const id = getItemId(item.value, group_id);
    const option = buildOption(doc, item, id);
    const link_selector_item: RenderedItem = {
        id,
        group_id,
        is_disabled: false,
        is_selected: false,
        value: item.value,
        target_option: option,
        template: item.template,
        element: getRenderedListItem(id, item.template, false),
    };
    accumulator.set(id, link_selector_item);
    return accumulator;
};

export class ListItemMapBuilder {
    public buildLinkSelectorItemsMap(groups: GroupCollection): RenderedItemMap {
        return groups.reduce((accumulator: RenderedItemMap, group) => {
            const group_id = getGroupId(group);
            return group.items.reduce(
                (inner_accumulator: RenderedItemMap, item) =>
                    addItemInMap(document, group_id, item, inner_accumulator),
                accumulator
            );
        }, new Map());
    }
}

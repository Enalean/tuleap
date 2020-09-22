/**
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

import { sanitize } from "dompurify";
import { ListPickerItem, ListPickerItemGroup } from "../type";
import { generateItemMapBasedOnSourceSelectOptions } from "./static-list-helper";

export function renderListPickerDropdownContent(
    source_select_box: HTMLSelectElement,
    component_dropdown: Element
): void {
    const item_map: Map<string, ListPickerItem> = generateItemMapBasedOnSourceSelectOptions(
        source_select_box
    );
    const component_values_list = component_dropdown.querySelector(
        ".list-picker-dropdown-values-list"
    );
    if (!(component_values_list instanceof Element)) {
        throw new Error("List element not found in list-picker dropdown");
    }

    const select_box_option_groups = source_select_box.querySelectorAll("optgroup");

    if (select_box_option_groups.length > 0) {
        renderGroupedOptions(item_map, component_values_list, select_box_option_groups);
        return;
    }

    Array.from(item_map.entries()).forEach(([item_id, current_item]) => {
        component_values_list.appendChild(getRenderedListItem(item_id, current_item));
    });
}

function renderGroupedOptions(
    item_map: Map<string, ListPickerItem>,
    component_values_list: Element,
    select_box_option_groups: NodeListOf<HTMLOptGroupElement>
): void {
    select_box_option_groups.forEach((optgroup) => {
        const group = getRenderedEmptyListItemGroup(component_values_list, optgroup);
        Array.from(item_map.values())
            .filter((item) => {
                return item.group_id === group.id;
            })
            .forEach((item) => {
                const rendered_item = getRenderedListItem(item.id, item);
                group.element.appendChild(rendered_item);
            });
    });
}

function getRenderedListItem(option_id: string, current_item: ListPickerItem): Element {
    const list_item = document.createElement("li");
    list_item.id = option_id;
    list_item.appendChild(document.createTextNode(sanitize(current_item.template)));
    list_item.setAttribute("role", "option");
    list_item.setAttribute("aria-selected", "false");

    if (current_item.is_disabled) {
        list_item.classList.add("list-picker-dropdown-option-value-disabled");
    } else {
        list_item.classList.add("list-picker-dropdown-option-value");
    }
    return list_item;
}

function getRenderedEmptyListItemGroup(
    component_values_list: Element,
    optgroup: HTMLOptGroupElement
): ListPickerItemGroup {
    const label = optgroup.getAttribute("label");

    if (!label) {
        throw new Error("Label attribute missing on optgroup element");
    }

    const group = document.createElement("li");
    const group_label = document.createElement("strong");

    group_label.innerText = label;
    group_label.classList.add("list-picker-group-label");

    const group_list = document.createElement("ul");
    group_list.setAttribute("role", "group");
    group_list.setAttribute("aria-label", label);
    group_list.classList.add("list-picker-dropdown-option-group");

    group.appendChild(group_label);
    group.appendChild(group_list);

    component_values_list.appendChild(group);

    return {
        id: label.replace(" ", "").toLowerCase(),
        label,
        element: group_list,
    };
}

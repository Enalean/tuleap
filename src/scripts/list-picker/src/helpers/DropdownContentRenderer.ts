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

import { ListPickerItem, ListPickerItemGroup } from "../type";

export class DropdownContentRenderer {
    constructor(
        private readonly source_select_box: HTMLSelectElement,
        private readonly dropdown_list_element: Element,
        private readonly item_map: Map<string, ListPickerItem>
    ) {}

    public renderListPickerDropdownContent(): void {
        const select_box_option_groups = this.source_select_box.querySelectorAll("optgroup");

        if (select_box_option_groups.length > 0) {
            this.renderGroupedOptions(select_box_option_groups);
            return;
        }

        Array.from(this.item_map.values()).forEach((current_item) => {
            this.dropdown_list_element.appendChild(current_item.element);
        });
    }

    private renderGroupedOptions(select_box_option_groups: NodeListOf<HTMLOptGroupElement>): void {
        select_box_option_groups.forEach((optgroup) => {
            const group = this.getRenderedEmptyListItemGroup(optgroup);
            Array.from(this.item_map.values())
                .filter((item) => {
                    return item.group_id === group.id;
                })
                .forEach((item) => {
                    const rendered_item = item.element;
                    group.element.appendChild(rendered_item);
                });
        });
    }

    private getRenderedEmptyListItemGroup(optgroup: HTMLOptGroupElement): ListPickerItemGroup {
        const label = optgroup.getAttribute("label");

        if (!label) {
            throw new Error("Label attribute missing on optgroup element");
        }

        const group = document.createElement("li");
        const group_label = document.createElement("strong");

        group.classList.add("list-picker-item-group");
        group_label.appendChild(document.createTextNode(label));
        group_label.classList.add("list-picker-group-label");

        const group_list = document.createElement("ul");
        group_list.setAttribute("role", "group");
        group_list.setAttribute("aria-label", label);
        group_list.classList.add("list-picker-dropdown-option-group");

        group.appendChild(group_label);
        group.appendChild(group_list);

        this.dropdown_list_element.appendChild(group);

        return {
            id: label.replace(" ", "").toLowerCase(),
            label,
            element: group_list,
        };
    }
}

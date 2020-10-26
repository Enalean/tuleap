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
import { GetText } from "../../../tuleap/gettext/gettext-init";

export class DropdownContentRenderer {
    private readonly groups_map: Map<string, ListPickerItemGroup>;

    constructor(
        private readonly source_select_box: HTMLSelectElement,
        private readonly dropdown_list_element: Element,
        private readonly item_map: Map<string, ListPickerItem>,
        private readonly gettext_provider: GetText
    ) {
        this.groups_map = new Map();
    }

    public renderListPickerDropdownContent(): void {
        if (this.hasGroupedListItems()) {
            this.renderGroupedOptions();
            return;
        }

        Array.from(this.item_map.values()).forEach((current_item) => {
            this.dropdown_list_element.appendChild(current_item.element);
        });
    }

    public renderFilteredListPickerDropdownContent(filter_query: string): void {
        this.dropdown_list_element.innerHTML = "";
        if (filter_query.length === 0) {
            this.renderListPickerDropdownContent();
            return;
        }

        const lowercase_query = filter_query.toLowerCase();
        const matching_items = Array.from(this.item_map.values()).filter((item) => {
            return item.template.toLowerCase().includes(lowercase_query);
        });

        if (matching_items.length === 0) {
            this.dropdown_list_element.appendChild(this.createEmptyDropdownState());
            return;
        }

        const displayed_groups_ids: string[] = [];
        matching_items.forEach((item) => {
            const group = this.groups_map.get(item.group_id);
            if (group && displayed_groups_ids.includes(item.group_id)) {
                group.list_element.appendChild(item.element);
            } else if (group) {
                displayed_groups_ids.push(group.id);
                group.list_element.innerHTML = "";
                group.list_element.appendChild(item.element);
                this.dropdown_list_element.appendChild(group.root_element);
            } else {
                this.dropdown_list_element.appendChild(item.element);
            }
        });
    }

    private hasGroupedListItems(): boolean {
        return this.source_select_box.querySelectorAll("optgroup").length > 0;
    }

    private renderGroupedOptions(): void {
        this.source_select_box.querySelectorAll("optgroup").forEach((optgroup) => {
            const group = this.getRenderedEmptyListItemGroup(optgroup);
            this.groups_map.set(group.id, group);

            Array.from(this.item_map.values())
                .filter((item) => {
                    return item.group_id === group.id;
                })
                .forEach((item) => {
                    group.list_element.appendChild(item.element);
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
            root_element: group,
            list_element: group_list,
        };
    }

    private createEmptyDropdownState(): Element {
        const empty_state = document.createElement("li");
        empty_state.classList.add("list-picker-empty-dropdown-state");
        empty_state.setAttribute("role", "alert");
        empty_state.setAttribute("aria-live", "assertive");
        empty_state.appendChild(
            document.createTextNode(this.gettext_provider.gettext("No results found"))
        );

        return empty_state;
    }
}

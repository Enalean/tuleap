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

import { sanitize } from "dompurify";
import { ListPickerItem, ListPickerItemMap, ListPickerOptions } from "../type";

export class ListItemMapBuilder {
    private items_templates_cache: Map<string, string>;

    constructor(
        private readonly source_select_box: HTMLSelectElement,
        private readonly options?: ListPickerOptions
    ) {
        this.items_templates_cache = new Map();
    }

    public async buildListPickerItemsMap(): Promise<ListPickerItemMap> {
        const map = new Map();
        const useless_options = [];

        for (const option of this.source_select_box.options) {
            if (option.value === "" || option.value === "?") {
                useless_options.push(option);
                continue;
            }

            let group_id = "";
            if (option.parentElement && option.parentElement.nodeName === "OPTGROUP") {
                const label = option.parentElement.getAttribute("label");

                if (label !== null) {
                    group_id = label.replace(" ", "").toLowerCase();
                }
            }

            const id = this.getItemId(option, group_id);
            const template = await this.getTemplateForItem(option, id);
            const is_disabled = Boolean(option.hasAttribute("disabled"));
            const item: ListPickerItem = {
                id,
                group_id,
                value: option.value,
                template,
                label: this.getOptionsLabel(option),
                is_disabled,
                is_selected: false,
                target_option: option,
                element: this.getRenderedListItem(id, template, is_disabled),
            };
            map.set(id, item);
            option.setAttribute("data-item-id", id);
        }

        useless_options.forEach((option) => this.source_select_box.removeChild(option));
        return map;
    }

    private getRenderedListItem(
        option_id: string,
        template: string,
        is_disabled: boolean
    ): HTMLElement {
        const list_item = document.createElement("li");
        list_item.appendChild(document.createTextNode(template));
        list_item.innerHTML = sanitize(template);
        list_item.setAttribute("role", "option");
        list_item.setAttribute("aria-selected", "false");
        list_item.setAttribute("data-item-id", option_id);

        if (is_disabled) {
            list_item.classList.add("list-picker-dropdown-option-value-disabled");
        } else {
            list_item.classList.add("list-picker-dropdown-option-value");
        }
        return list_item;
    }

    private getItemId(option: HTMLOptionElement, group_id: string): string {
        let base_id = "list-picker-item-";
        let option_value = option.value.toLowerCase().trim();

        if (group_id !== "") {
            base_id += group_id + "-";
        }

        if (option_value.includes(" ")) {
            option_value = option_value.split(" ").join("-");
        }

        return base_id + option_value;
    }

    private async getTemplateForItem(option: HTMLOptionElement, item_id: string): Promise<string> {
        const template = this.items_templates_cache.get(item_id);
        if (template) {
            return template;
        }

        const option_label = this.getOptionsLabel(option);
        if (this.options && this.options.items_template_formatter) {
            const custom_template = await this.options.items_template_formatter(
                option.value,
                option_label
            );

            const sanitized_template = sanitize(custom_template, {
                ALLOWED_TAGS: ["span", "div", "img", "i"],
            });
            this.items_templates_cache.set(item_id, sanitized_template);
            return sanitized_template;
        }

        return option_label;
    }

    private getOptionsLabel(option: HTMLOptionElement): string {
        return option.innerText !== "" && option.innerText !== undefined
            ? option.innerText
            : option.label;
    }
}

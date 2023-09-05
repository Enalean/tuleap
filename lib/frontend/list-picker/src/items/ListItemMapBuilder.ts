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

import type { ListPickerItem, ListPickerItemMap, ListPickerOptions } from "../type";
import type { TemplateResult, HTMLTemplateResult } from "lit/html.js";
import { html, render } from "lit/html.js";
import { getOptionsLabel } from "../helpers/option-label-helper";
import {
    hasOptionPredefinedTemplate,
    retrievePredefinedTemplate,
} from "../helpers/templates/predefined-item-template-retriever-helper";

export class ListItemMapBuilder {
    private items_templates_cache: Map<string, TemplateResult>;

    constructor(
        private readonly source_select_box: HTMLSelectElement,
        private readonly options?: ListPickerOptions,
    ) {
        this.items_templates_cache = new Map();
    }

    public buildListPickerItemsMap(): ListPickerItemMap {
        const map = new Map();
        const useless_options = [];

        for (const option of this.source_select_box.options) {
            if (option.value === "" && option.innerHTML === "") {
                // Do not remove, otherwise the default value will be the first value of the list
                // and no placeholder is shown.
                continue;
            }

            if (option.value === "?") {
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
            const template = this.getTemplateForItem(option, id);
            const is_disabled = Boolean(option.hasAttribute("disabled"));
            const item: ListPickerItem = {
                id,
                group_id,
                value: option.value,
                template,
                label: getOptionsLabel(option),
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
        template: TemplateResult,
        is_disabled: boolean,
    ): Element {
        let class_name = "list-picker-dropdown-option-value";
        if (is_disabled) {
            class_name = "list-picker-dropdown-option-value-disabled";
        }

        const document_fragment = document.createDocumentFragment();
        render(
            html`
                <li
                    role="option"
                    aria-selected="false"
                    data-item-id="${option_id}"
                    class="${class_name}"
                    data-test="list-picker-item"
                >
                    ${template}
                </li>
            `,
            document_fragment,
        );

        const list_item = document_fragment.firstElementChild;
        if (list_item !== null) {
            return list_item;
        }

        throw new Error("Cannot render the list item");
    }

    private getItemId(option: HTMLOptionElement, group_id: string): string {
        let base_id = "list-picker-item-";
        let option_value = option.value.toLowerCase().trim();

        if (option_value === "100" || option_value === "number:100") {
            return base_id + "100";
        }

        if (group_id !== "") {
            base_id += group_id + "-";
        }

        if (option_value.includes(" ")) {
            option_value = option_value.split(" ").join("-");
        }

        return base_id + option_value;
    }

    private getTemplateForItem(option: HTMLOptionElement, item_id: string): TemplateResult {
        const template = this.items_templates_cache.get(item_id);
        if (template) {
            return template;
        }

        const option_label = getOptionsLabel(option);
        if (hasOptionPredefinedTemplate(option)) {
            return retrievePredefinedTemplate(option);
        }

        if (this.options && this.options.items_template_formatter) {
            const custom_template = this.options.items_template_formatter(
                html,
                option.value,
                option_label,
            );
            this.items_templates_cache.set(item_id, custom_template);

            return custom_template;
        }

        return ListItemMapBuilder.buildDefaultTemplateForItem(option_label);
    }

    public static buildDefaultTemplateForItem(value: string): HTMLTemplateResult {
        return html`${value}`;
    }
}

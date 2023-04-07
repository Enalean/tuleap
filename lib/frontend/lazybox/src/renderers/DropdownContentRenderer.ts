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

import { html, render } from "lit/html.js";
import type { HTMLTemplateResult } from "lit/html.js";
import type { ItemsMapManager } from "../items/ItemsMapManager";
import type { GroupCollection } from "../items/GroupCollection";
import { getGroupId } from "../helpers/group-id-helper";
import type { GroupOfItems } from "../items/GroupCollection";

export const getRenderedListItem = (
    option_id: string,
    template: HTMLTemplateResult,
    is_disabled: boolean
): Element => {
    let class_name = "lazybox-dropdown-option-value";
    if (is_disabled) {
        class_name = "lazybox-dropdown-option-value-disabled";
    }

    const document_fragment = document.createDocumentFragment();
    render(
        html`
            <li
                role="option"
                aria-selected="false"
                data-item-id="${option_id}"
                class="${class_name}"
                data-test="lazybox-item"
            >
                ${template}
            </li>
        `,
        document_fragment
    );

    const list_item = document_fragment.firstElementChild;
    if (list_item !== null) {
        return list_item;
    }

    throw new Error("Cannot render the list item");
};

const createEmptyDropdownState = (dropdown_message: string): HTMLTemplateResult =>
    html`
        <li
            class="lazybox-empty-dropdown-state"
            role="alert"
            aria-live="assertive"
            data-test="lazybox-empty-state"
        >
            ${dropdown_message}
        </li>
    `;

const getGroupLabel = (group: GroupOfItems): HTMLTemplateResult => {
    if (group.is_loading) {
        return html`
            <strong class="lazybox-group-label">
                ${group.label}
                <i
                    class="fa-solid fa-spin fa-circle-notch lazybox-loading-group-spinner"
                    data-test="lazybox-loading-group-spinner"
                ></i>
            </strong>
        `;
    }

    return html`<strong class="lazybox-group-label">${group.label}</strong>`;
};

const getGroupFooter = (group: GroupOfItems): HTMLTemplateResult =>
    group.footer_message !== "" && group.items.length > 0
        ? html`<p class="lazybox-group-footer" data-test="lazybox-group-footer">
              ${group.footer_message}
          </p>`
        : html``;

export class DropdownContentRenderer {
    constructor(
        private readonly dropdown_list_element: HTMLElement,
        private readonly items_map_manager: ItemsMapManager
    ) {}

    public renderLazyboxDropdownContent(groups: GroupCollection): void {
        const group_templates = groups.map((group) => {
            const group_id = getGroupId(group);
            const lazybox_items = this.items_map_manager
                .getLazyboxItems()
                .filter((item) => item.group_id === group_id);

            const items_template =
                group.items.length === 0
                    ? createEmptyDropdownState(group.empty_message)
                    : lazybox_items.map((item) => item.element);

            return html`
                <li class="lazybox-item-group">
                    ${getGroupLabel(group)}
                    <ul
                        role="group"
                        aria-label="${group.label}"
                        class="lazybox-dropdown-option-group"
                    >
                        ${items_template}
                    </ul>
                    ${getGroupFooter(group)}
                </li>
            `;
        });

        render(group_templates, this.dropdown_list_element);
    }
}

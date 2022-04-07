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

import type { LinkSelectorItemGroup } from "../type";
import type { ItemsMapManager } from "../items/ItemsMapManager";
import type { GettextProvider } from "@tuleap/gettext";
import { html, render } from "lit/html.js";

export class DropdownContentRenderer {
    private readonly groups_map: Map<string, LinkSelectorItemGroup>;

    constructor(
        private readonly source_select_box: HTMLSelectElement,
        private readonly dropdown_list_element: Element,
        private readonly items_map_manager: ItemsMapManager,
        private readonly gettext_provider: GettextProvider
    ) {
        this.groups_map = new Map();
    }

    public renderLinkSelectorDropdownContent(): void {
        const items = this.items_map_manager.getLinkSelectorItems();
        if (items.length === 0) {
            this.appendEmptyStates();
            return;
        }

        if (this.hasGroupedListItems()) {
            this.renderGroupedOptions();
            return;
        }

        items.forEach((current_item) => {
            this.dropdown_list_element.appendChild(current_item.element);
        });
    }

    public renderAfterDependenciesUpdate(): void {
        this.dropdown_list_element.innerHTML = "";
        if (this.items_map_manager.getLinkSelectorItems().length === 0) {
            this.appendEmptyStates();
            return;
        }
        this.renderLinkSelectorDropdownContent();
    }

    private hasGroupedListItems(): boolean {
        return this.source_select_box.querySelectorAll("optgroup").length > 0;
    }

    private renderGroupedOptions(): void {
        this.source_select_box.querySelectorAll("optgroup").forEach((optgroup) => {
            const group = this.getRenderedEmptyListItemGroup(optgroup);
            this.groups_map.set(group.id, group);

            this.items_map_manager
                .getLinkSelectorItems()
                .filter((item) => {
                    return item.group_id === group.id;
                })
                .forEach((item) => {
                    group.list_element.appendChild(item.element);
                    this.dropdown_list_element.appendChild(group.root_element);
                });
        });
    }

    private renderGroupedOptionsEmptyStates(): void {
        this.source_select_box.querySelectorAll("optgroup").forEach((optgroup) => {
            const group = this.getRenderedEmptyListItemGroup(optgroup);
            const group_empty_state = this.getOptionGroupEmptyState(optgroup);

            if (group_empty_state && group_empty_state.textContent) {
                group.list_element.appendChild(
                    this.createEmptyDropdownState(group_empty_state.textContent)
                );

                this.dropdown_list_element.appendChild(group.root_element);
            }
        });
    }

    private getOptionGroupEmptyState(element: Element): HTMLOptionElement | null {
        return element.querySelector("option[data-link-selector-role=empty-state]");
    }

    private getRenderedEmptyListItemGroup(optgroup: HTMLOptGroupElement): LinkSelectorItemGroup {
        const label = optgroup.getAttribute("label");

        if (!label) {
            throw new Error("Label attribute missing on optgroup element");
        }

        const group = document.createElement("li");
        const group_label = document.createElement("strong");

        group.classList.add("link-selector-item-group");
        group_label.appendChild(document.createTextNode(label));
        group_label.classList.add("link-selector-group-label");

        const group_list = document.createElement("ul");
        group_list.setAttribute("role", "group");
        group_list.setAttribute("aria-label", label);
        group_list.classList.add("link-selector-dropdown-option-group");

        group.appendChild(group_label);
        group.appendChild(group_list);

        return {
            id: label.replace(" ", "").toLowerCase(),
            label,
            root_element: group,
            list_element: group_list,
        };
    }

    private createEmptyStateNoValuesAvailable(): DocumentFragment {
        return this.createEmptyDropdownState(this.gettext_provider.gettext("No values to select"));
    }

    private createEmptyDropdownState(dropdown_message: string): DocumentFragment {
        const document_fragment = document.createDocumentFragment();
        render(
            html`
                <li
                    class="link-selector-empty-dropdown-state"
                    role="alert"
                    aria-live="assertive"
                    data-test="link-selector-empty-state"
                >
                    ${dropdown_message}
                </li>
            `,
            document_fragment
        );

        return document_fragment;
    }

    private appendEmptyStates(): void {
        if (this.hasGroupedListItems()) {
            this.renderGroupedOptionsEmptyStates();
            return;
        }

        this.dropdown_list_element.appendChild(this.createEmptyStateNoValuesAvailable());
    }
}

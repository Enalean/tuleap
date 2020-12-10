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

import { ListPickerItem, ListPickerSelectionStateMultiple, SelectionManager } from "../type";
import { DropdownManager } from "../dropdown/DropdownManager";
import { ItemsMapManager } from "../items/ItemsMapManager";
import { GettextProvider } from "../../../tuleap/gettext/gettext-sync";
import { html, render } from "lit-html";
import { classMap } from "lit-html/directives/class-map";
import { ListItemMapBuilder } from "../items/ListItemMapBuilder";

export class MultipleSelectionManager implements SelectionManager {
    private readonly selection_state: ListPickerSelectionStateMultiple;
    private readonly clear_selection_state_button_element: Element;

    private static NONE_ITEM_ID = "list-picker-item-100";

    constructor(
        private readonly source_select_box: HTMLSelectElement,
        private readonly selection_element: Element,
        private readonly search_field_element: HTMLInputElement,
        private readonly placeholder_text: string,
        private readonly dropdown_manager: DropdownManager,
        private readonly items_map_manager: ItemsMapManager,
        private readonly gettext_provider: GettextProvider
    ) {
        this.selection_state = {
            selected_items: new Map(),
            selected_value_elements: new Map(),
        };

        this.clear_selection_state_button_element = this.createClearSelectionStateButton();
    }

    public processSelection(item: Element): void {
        if (!(item instanceof HTMLElement) || !item.dataset.itemId) {
            throw new Error("No data-item-id found on element.");
        }

        const item_id = item.dataset.itemId;
        const list_item = this.items_map_manager.findListPickerItemInItemMap(item_id);
        if (list_item.is_selected) {
            this.removeListItemFromSelection(list_item);
            this.togglePlaceholder();
            this.toggleClearValuesButton();
            return;
        }

        this.unselectOtherValuesIfNoneIsSelected(item_id);
        this.unselectNoneValueIfOtherValueSelected(item_id);

        this.selection_state.selected_items.set(list_item.id, list_item);
        const badge = this.createItemBadgeElement(list_item);
        this.selection_state.selected_value_elements.set(list_item.id, badge);

        this.selection_element.insertBefore(badge, this.search_field_element.parentElement);
        list_item.is_selected = true;
        list_item.element.setAttribute("aria-selected", "true");
        list_item.target_option.setAttribute("selected", "selected");
        list_item.target_option.selected = true;

        this.source_select_box.dispatchEvent(new Event("change"));

        this.togglePlaceholder();
        this.toggleClearValuesButton();
    }

    private unselectNoneValueIfOtherValueSelected(item_id: string): void {
        if (
            item_id !== MultipleSelectionManager.NONE_ITEM_ID &&
            this.selection_state.selected_items.has(MultipleSelectionManager.NONE_ITEM_ID)
        ) {
            const none_item = this.items_map_manager.findListPickerItemInItemMap(
                MultipleSelectionManager.NONE_ITEM_ID
            );
            this.removeListItemFromSelection(none_item);
            this.togglePlaceholder();
            this.toggleClearValuesButton();
        }
    }

    private unselectOtherValuesIfNoneIsSelected(item_id: string): void {
        if (item_id === MultipleSelectionManager.NONE_ITEM_ID) {
            this.selection_state.selected_items.forEach((selected_item: ListPickerItem) => {
                this.removeListItemFromSelection(selected_item);
            });
            this.togglePlaceholder();
            this.toggleClearValuesButton();
        }
    }

    public initSelection(): void {
        for (const option of this.source_select_box.options) {
            if (!option.selected || !option.value) {
                continue;
            }
            const item_to_select = this.items_map_manager.getItemWithValue(option.value);
            if (item_to_select) {
                this.processSelection(item_to_select.element);
            }
        }
    }

    public handleBackspaceKey(event: KeyboardEvent): void {
        const nb_selected_items = this.selection_state.selected_items.size;
        if (nb_selected_items === 0 && this.search_field_element.value.length === 1) {
            // User has deleted the last letter of the query, and no item is selected so let's only display the placeholder
            this.togglePlaceholder();
            return;
        }

        if (nb_selected_items === 0 || this.search_field_element.value !== "") {
            // Either there is no selected item anymore, either the user is deleting the query, so do nothing
            return;
        }

        const last_selected_item = Array.from(this.selection_state.selected_items.values())[
            this.selection_state.selected_items.size - 1
        ];

        this.removeListItemFromSelection(last_selected_item);
        this.toggleClearValuesButton();

        this.search_field_element.value = last_selected_item.label;
        event.preventDefault();
        event.cancelBubble = true;
    }

    public resetAfterDependenciesUpdate(): void {
        const selected_items: Array<ListPickerItem> = [];
        this.selection_state.selected_items.forEach((item) => {
            const item_to_select = this.items_map_manager.getItemWithValue(item.value);
            if (item_to_select === null) {
                return;
            }
            selected_items.push(item_to_select);
        });

        this.clearSelectionState(false);
        this.source_select_box.value = "";
        selected_items.forEach((item) => this.processSelection(item.element));

        this.togglePlaceholder();
        this.toggleClearValuesButton();
    }

    private togglePlaceholder(): void {
        if (this.selection_state.selected_value_elements.size === 0) {
            this.search_field_element.setAttribute("placeholder", this.placeholder_text);
            return;
        }

        this.search_field_element.removeAttribute("placeholder");
    }

    private toggleClearValuesButton(): void {
        if (this.source_select_box.disabled) {
            return;
        }

        if (this.selection_state.selected_value_elements.size === 0) {
            this.removeClearSelectionStateButton();
            return;
        }

        if (!this.selection_element.contains(this.clear_selection_state_button_element)) {
            this.selection_element.insertAdjacentElement(
                "beforeend",
                this.clear_selection_state_button_element
            );
        }
    }

    private removeClearSelectionStateButton(): void {
        if (!this.selection_element.contains(this.clear_selection_state_button_element)) {
            return;
        }
        this.selection_element.removeChild(this.clear_selection_state_button_element);
    }

    private createClearSelectionStateButton(): Element {
        const remove_value_button = document.createElement("span");
        remove_value_button.classList.add("list-picker-selected-value-remove-button");
        remove_value_button.innerText = "×";
        remove_value_button.setAttribute(
            "title",
            this.gettext_provider.gettext("Remove all values")
        );

        remove_value_button.addEventListener("pointerdown", (event: Event) => {
            event.preventDefault();
            event.cancelBubble = true;

            this.clearSelectionState(true);
            this.togglePlaceholder();
            this.removeClearSelectionStateButton();
            this.dropdown_manager.openListPicker();
        });

        return remove_value_button;
    }

    private createItemBadgeElement(list_item: ListPickerItem): Element {
        const remove_button_event_listener = (event: Event): void => {
            if (this.source_select_box.disabled) {
                return;
            }
            event.preventDefault();
            event.cancelBubble = true;

            this.processSelection(list_item.element);
            this.dropdown_manager.openListPicker();
        };

        const badge_classes = {
            "list-picker-badge": true,
            "list-picker-badge-custom":
                list_item.template.getHTML() !==
                ListItemMapBuilder.buildDefaultTemplateForItem(list_item.label).getHTML(),
        };

        const badge_template = html`
            <span class="${classMap(badge_classes)}" title="${list_item.label}">
                <span
                    role="presentation"
                    class="list-picker-value-remove-button"
                    @pointerup=${remove_button_event_listener}
                >
                    &times;
                </span>
                ${list_item.template}
            </span>
        `;

        const badge_document_fragment = document.createDocumentFragment();
        render(badge_template, badge_document_fragment);

        const badge_document_element = badge_document_fragment.firstElementChild;
        if (badge_document_element !== null && badge_document_fragment.children.length === 1) {
            return badge_document_element;
        }
        throw new Error("Cannot create item badge element");
    }

    private removeListItemFromSelection(
        list_item: ListPickerItem,
        is_clearing_selection = false
    ): void {
        const badge = this.selection_state.selected_value_elements.get(list_item.id);
        const selected_item = this.selection_state.selected_items.get(list_item.id);

        if (!badge || !selected_item) {
            throw new Error("Item not found in selection state.");
        }

        this.selection_element.removeChild(badge);
        this.selection_state.selected_value_elements.delete(list_item.id);
        this.selection_state.selected_items.delete(list_item.id);

        list_item.is_selected = false;
        list_item.element.setAttribute("aria-selected", "false");
        list_item.target_option.removeAttribute("selected");
        list_item.target_option.selected = false;

        if (!is_clearing_selection) {
            this.source_select_box.dispatchEvent(new Event("change"));
        }
    }

    private clearSelectionState(should_change_be_dispatched: boolean): void {
        Array.from(this.selection_state.selected_items.values()).forEach((item) => {
            this.removeListItemFromSelection(item, true);
        });

        if (should_change_be_dispatched) {
            this.source_select_box.dispatchEvent(new Event("change"));
        }
    }
}

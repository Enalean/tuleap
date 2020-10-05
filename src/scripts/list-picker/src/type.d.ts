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

export interface ListPicker {
    destroy: () => void;
}

export interface ListPickerOptions {
    placeholder?: string;
    is_filterable?: boolean;
}

export interface ListPickerItem {
    id: string;
    template: string;
    is_disabled: boolean;
    is_selected: boolean;
    group_id: string;
    element: Element;
    target_option: Element;
}

export interface ListPickerItemGroup {
    id: string;
    label: string;
    root_element: Element;
    list_element: Element;
}

export interface ListPickerComponent {
    wrapper_element: Element;
    list_picker_element: Element;
    dropdown_element: Element;
    selection_element: Element;
    placeholder_element: Element;
    dropdown_list_element: Element;
    search_field_element: HTMLInputElement;
}

export interface ListPickerSelectionStateSingle {
    selected_item: ListPickerItem;
    selected_value_element: Element;
}

export interface ListPickerSelectionStateMultiple {
    selected_items: Map<string, ListPickerItem>;
    selected_value_elements: Map<string, Element>;
}

export interface SelectionManager {
    readonly processSelection: (element: Element) => void;
    readonly initSelection: () => void;
    readonly handleBackspaceKey: (event: KeyboardEvent) => void;
}

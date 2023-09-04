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

import type { html as HTMLTemplateStringProcessor, TemplateResult } from "lit/html.js";

export interface ListPicker {
    destroy: () => void;
}

export interface ListPickerOptions {
    readonly locale?: string | undefined;
    placeholder?: string;
    is_filterable?: boolean;
    none_value?: string | null;
    items_template_formatter?: (
        html: typeof HTMLTemplateStringProcessor,
        value_id: string,
        item_label: string,
    ) => TemplateResult;
}

export type ListPickerItemMap = Map<string, ListPickerItem>;

export interface ListPickerItem {
    id: string;
    template: TemplateResult;
    label: string;
    value: string;
    is_disabled: boolean;
    is_selected: boolean;
    group_id: string;
    element: Element;
    target_option: HTMLOptionElement;
}

export interface LegacyColorRGB {
    red: number;
    green: number;
    blue: number;
}

export interface ListPickerItemGroup {
    id: string;
    label: string;
    root_element: Element;
    list_element: Element;
}

export interface ListPickerComponent {
    wrapper_element: HTMLElement;
    list_picker_element: Element;
    dropdown_element: HTMLElement;
    selection_element: HTMLElement;
    placeholder_element: Element;
    dropdown_list_element: Element;
    search_field_element: HTMLInputElement;
    element_attributes_updater: () => void;
}

export interface ListPickerSelectionStateSingle {
    selected_item: ListPickerItem;
}

export interface ListPickerSelectionStateMultiple {
    selected_items: ListPickerItemMap;
    selected_value_elements: Map<string, Element>;
}

export interface SelectionManager {
    readonly processSelection: (element: Element) => void;
    readonly initSelection: () => void;
    readonly handleBackspaceKey: (event: KeyboardEvent) => void;
    readonly resetAfterChangeInOptions: () => void;
}

export interface ScrollCoordinates {
    x_position: number;
    y_position: number;
}

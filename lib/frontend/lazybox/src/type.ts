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

import type { HTMLTemplateResult, TemplateResult } from "lit/html.js";
import type { GroupCollection, LazyboxItem } from "./items/GroupCollection";
import type { HTMLTemplateStringProcessor } from "./index";

export interface Lazybox {
    setDropdownContent: (groups: GroupCollection) => void;
    resetSelection: () => void;
    setSelection: (selection: ReadonlyArray<LazyboxItem>) => void;
    destroy: () => void;
}

export type LazyboxSearchFieldCallback = (query: string) => void;

export type LazyboxSelectionCallback = (selected_value: unknown | null) => void;

export type LazyboxTemplatingCallback = (
    html: typeof HTMLTemplateStringProcessor,
    item: LazyboxItem
) => HTMLTemplateResult;

export type LazyboxNewItemCallback = () => void;

export type LazyBoxWithNewItemButton = {
    readonly new_item_button_label: string;
    readonly new_item_callback: LazyboxNewItemCallback;
};

type LazyBoxWithoutNewItemButton = {
    readonly new_item_callback?: undefined;
};

export type LazyboxOptions = (LazyBoxWithoutNewItemButton | LazyBoxWithNewItemButton) & {
    readonly placeholder: string;
    readonly search_input_placeholder: string;
    readonly is_multiple: boolean;
    readonly templating_callback: LazyboxTemplatingCallback;
    readonly selection_callback: LazyboxSelectionCallback;
    readonly search_field_callback: LazyboxSearchFieldCallback;
};

export type RenderedItemMap = Map<string, RenderedItem>;

export interface RenderedItem {
    id: string;
    template: TemplateResult;
    value: unknown;
    is_disabled: boolean;
    is_selected: boolean;
    group_id: string;
    element: Element;
}

export interface LazyboxComponent {
    wrapper_element: HTMLElement;
    lazybox_element: Element;
    dropdown_element: HTMLElement;
    selection_element: HTMLElement;
    placeholder_element: Element;
    dropdown_list_element: HTMLElement;
    search_field_element: HTMLInputElement;
}

export interface LazyboxSelectionStateSingle {
    selected_item: RenderedItem;
    selected_value_element: DocumentFragment;
}

export interface LazyboxSelectionStateMultiple {
    selected_items: Map<string, RenderedItem>;
    selected_values_elements: Map<string, Element>;
}

export interface ScrollCoordinates {
    x_position: number;
    y_position: number;
}

export interface ManageSelection {
    processSelection(item: Element): void;
    hasSelection(): boolean;
    updateSelectionAfterDropdownContentChange(): void;
    clearSelection(): void;
    setSelection(selection: ReadonlyArray<RenderedItem>): void;
}

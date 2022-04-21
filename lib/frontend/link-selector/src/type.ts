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

import type { TemplateResult } from "lit/html.js";
import type { LinkSelectorSearchFieldCallback } from "./events/SearchFieldEventCallbackHandler";

export interface LinkSelector {
    destroy: () => void;
}

export interface LinkSelectorOptions {
    readonly placeholder?: string;
    search_field_callback: LinkSelectorSearchFieldCallback;
}

export type RenderedItemMap = Map<string, RenderedItem>;

export interface RenderedItem {
    id: string;
    template: TemplateResult;
    value: string;
    is_disabled: boolean;
    is_selected: boolean;
    group_id: string;
    element: Element;
    target_option: HTMLOptionElement;
}

export interface LinkSelectorComponent {
    wrapper_element: HTMLElement;
    link_selector_element: Element;
    dropdown_element: HTMLElement;
    selection_element: HTMLElement;
    placeholder_element: Element;
    dropdown_list_element: HTMLElement;
    search_field_element: HTMLInputElement;
}

export interface LinkSelectorSelectionStateSingle {
    selected_item: RenderedItem;
    selected_value_element: DocumentFragment;
}

export interface ScrollCoordinates {
    x_position: number;
    y_position: number;
}

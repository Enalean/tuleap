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

import { render } from "mustache";
import { sanitize } from "dompurify";
import { ListPickerOptions } from "./type";
import { DropdownContentRenderer } from "./helpers/DropdownContentRenderer";
import { SelectionManager } from "./helpers/SelectionManager";
import { EventManager } from "./helpers/EventManager";
import { DropdownToggler } from "./helpers/DropdownToggler";

export function createListPicker(
    source_select_box: HTMLSelectElement,
    options?: ListPickerOptions
): void {
    if (source_select_box === null || source_select_box.parentElement === null) {
        return;
    }

    const rendered_list_picker = getRenderedListPicker(source_select_box.parentElement);

    hideSourceSelectBox();

    source_select_box.insertAdjacentHTML("afterend", sanitize(rendered_list_picker));

    const parent_element = source_select_box.parentElement;
    const component_wrapper = parent_element.querySelector(".list-picker-wrapper");
    const component_root = parent_element.querySelector(".list-picker");
    const component_dropdown = parent_element.querySelector(".list-picker-dropdown");

    if (
        !isElement(component_root) ||
        !isElement(component_dropdown) ||
        !isElement(component_wrapper)
    ) {
        throw new Error("List picker not found in DOM.");
    }

    const placeholder_element = component_root.querySelector(".list-picker-placeholder");
    const selection_element = component_root.querySelector(".list-picker-selection");

    if (!isElement(placeholder_element) || !isElement(selection_element)) {
        throw new Error("List picker not rendered properly.");
    }

    function isElement(element: Element | null): element is Element {
        return element !== null && element instanceof Element;
    }

    const dropdown_toggler = new DropdownToggler(component_root, component_dropdown);
    const selection_manager = new SelectionManager(
        source_select_box,
        component_dropdown,
        selection_element,
        placeholder_element,
        dropdown_toggler
    );
    const dropdown_content_renderer = new DropdownContentRenderer(
        source_select_box,
        component_dropdown
    );

    dropdown_content_renderer.renderListPickerDropdownContent();

    const event_manager = new EventManager(
        document,
        component_wrapper,
        component_dropdown,
        source_select_box,
        selection_manager,
        dropdown_toggler
    );

    event_manager.attachEvents();
    selection_manager.initSelection(placeholder_element);

    function getRenderedListPicker(parent_element: HTMLElement): string {
        const parent_element_width = parent_element.clientWidth;

        return render(
            `
                <span class="list-picker-wrapper">
                    <span class="list-picker{{# is_disabled }} list-picker-disabled {{/ is_disabled }}">
                        <span class="list-picker-selection list-picker-single" role="textbox" aria-readonly="true">
                           <span class="list-picker-placeholder">{{ placeholder }}</span>
                        </span>
                    </span>
                    <span class="list-picker-dropdown" style="width: {{ parent_element_width }}px">
                        <ul
                            class="list-picker-dropdown-values-list"
                            role="listbox"
                            aria-expanded="false"
                            aria-hidden="false"
                        ></ul>
                    </span>
                </span>
            `,
            {
                placeholder: options?.placeholder,
                is_disabled: isComponentDisabled(),
                parent_element_width,
            }
        );
    }

    function hideSourceSelectBox(): void {
        source_select_box.classList.add("list-picker-hidden-accessible");
        source_select_box.setAttribute("tabindex", "-1");
        source_select_box.setAttribute("aria-hidden", "true");
    }

    function isComponentDisabled(): boolean {
        return Boolean(source_select_box.disabled);
    }
}

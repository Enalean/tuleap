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
import { renderListPickerDropdownContent } from "./helpers/dropdown-content-renderer";
import { ListPickerOptions } from "./type";
import { attachEvents } from "./helpers/list-picker-events-helper";

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
    const component_root = parent_element.querySelector(".list-picker");
    const component_dropdown = parent_element.querySelector(".list-picker-dropdown");

    if (!isElement(component_root) || !isElement(component_dropdown)) {
        throw new Error("List picker not found in DOM.");
    }

    function isElement(element: Element | EventTarget | null): element is Element {
        return element !== null && element instanceof Element;
    }

    attachEvents(document, source_select_box, component_root, component_dropdown);
    renderListPickerDropdownContent(source_select_box, component_dropdown);

    function getRenderedListPicker(parent_element: HTMLElement): string {
        const parent_element_width = parent_element.clientWidth;

        return render(
            `
                <span class="list-picker{{# is_disabled }} list-picker-disabled {{/ is_disabled }}">
                    <span class="list-picker-single" role="textbox" aria-readonly="true">
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

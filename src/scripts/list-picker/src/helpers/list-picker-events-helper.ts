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
import { closeListPicker, openListPicker } from "./dropdown-helper";

export function attachEvents(
    doc: HTMLDocument,
    source_select_box: HTMLSelectElement,
    component_root: Element,
    component_dropdown: Element
): void {
    component_root.addEventListener("click", () => {
        if (source_select_box.disabled) {
            return;
        }

        if (component_dropdown.classList.contains("list-picker-dropdown-shown")) {
            closeListPicker(component_root, component_dropdown);
        } else {
            openListPicker(component_root, component_dropdown);
        }
    });

    doc.addEventListener("keyup", (event: Event): void => {
        if (
            event instanceof KeyboardEvent &&
            (event.key === "Escape" || event.key === "Esc" || event.keyCode === 27)
        ) {
            closeListPicker(component_root, component_dropdown);
        }
    });

    doc.addEventListener("click", (event: Event): void => {
        const target_element = event.target;

        if (!(target_element instanceof Element)) {
            return closeListPicker(component_root, component_dropdown);
        }

        if (!component_root.contains(target_element)) {
            return closeListPicker(component_root, component_dropdown);
        }
    });
}

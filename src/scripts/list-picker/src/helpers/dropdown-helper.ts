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

export function closeListPicker(component_root: Element, component_dropdown: Element): void {
    component_dropdown.classList.remove("list-picker-dropdown-shown");
    component_root.classList.remove("list-picker-with-open-dropdown");

    const list = component_dropdown.querySelector(".list-picker-dropdown-values-list");

    if (list) {
        list.setAttribute("aria-expanded", "false");
    }
}

export function openListPicker(component_root: Element, component_dropdown: Element): void {
    component_dropdown.classList.add("list-picker-dropdown-shown");
    component_root.classList.add("list-picker-with-open-dropdown");

    const list = component_dropdown.querySelector(".list-picker-dropdown-values-list");

    if (list) {
        list.setAttribute("aria-expanded", "true");
    }
}

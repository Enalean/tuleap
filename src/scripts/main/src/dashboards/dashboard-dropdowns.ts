/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

import { createDropdown } from "@tuleap/tlp-dropdown";
import { applyLayout } from "./dashboard-layout";

function init(): void {
    const cogs = document.querySelectorAll(
        ".dashboard-widget-actions, #dashboard-tabs-dropdown-trigger",
    );

    for (const cog of cogs) {
        createDropdown(cog);
    }

    const template_dropdown = document.getElementById("dashboard-layout-dropdown-template");

    if (template_dropdown !== null) {
        initLayoutDropdowns();
    }
}

export { init as default };

function initLayoutDropdowns(): void {
    const all_rows = document.querySelectorAll(".dashboard-widgets-row");

    [].forEach.call(all_rows, function (row) {
        addLayoutDropdown(row);
    });
}

export function addLayoutDropdown(row: HTMLElement): void {
    const template_dropdown = document.getElementById("dashboard-layout-dropdown-template");

    if (template_dropdown === null) {
        return;
    }

    const dropdown_container = cloneLayoutDropdown(row, template_dropdown);
    const dropdown_button = dropdown_container.querySelector(".dashboard-row-dropdown-button");
    if (!dropdown_button) {
        throw new Error("No button with class dashboard-row-dropdown-button");
    }
    initLayoutDropdown(dropdown_button, row);
}

function initLayoutDropdown(dropdown_button: Element, row: HTMLElement): void {
    const tlp_dropdown = createDropdown(dropdown_button);
    const menu = tlp_dropdown.getDropdownMenu();
    if (!menu) {
        throw new Error("No menu in dropdown");
    }
    initLayoutChangeButtons(menu, row);

    tlp_dropdown.addEventListener("tlp-dropdown-shown", function (event) {
        const current_dropdown = event.detail.target;
        const parent_container = current_dropdown.parentElement;
        if (!parent_container) {
            throw new Error("No parent element of dropdown");
        }
        const nb_columns = row.querySelectorAll(".dashboard-widgets-column").length;
        const current_layout = row.dataset.currentLayout;
        if (!current_layout) {
            throw new Error("No currentLayout in data of row");
        }

        parent_container.classList.add("shown");
        row.classList.add("shake-widgets");
        hideUnapplicableLayoutsAndCheckCurrentLayout(current_dropdown, nb_columns, current_layout);
    });
    tlp_dropdown.addEventListener("tlp-dropdown-hidden", function (event) {
        const current_dropdown = event.detail.target;
        const parent_container = current_dropdown.parentElement;
        if (!parent_container) {
            throw new Error("No parent element of dropdown");
        }
        parent_container.classList.remove("shown");

        row.classList.remove("shake-widgets");
    });
}

function cloneLayoutDropdown(row: HTMLElement, template_dropdown: HTMLElement): Element {
    const cloned_dropdown = template_dropdown.cloneNode(true);
    if (!(cloned_dropdown instanceof HTMLElement)) {
        throw new Error("Clone of row is not HTMLElement");
    }
    cloned_dropdown.removeAttribute("id");

    row.appendChild(cloned_dropdown);

    return cloned_dropdown;
}

function initLayoutChangeButtons(dropdown: HTMLElement, row: HTMLElement): void {
    const radio_buttons = dropdown.querySelectorAll(".dashboard-dropdown-layout-field");

    [].forEach.call(radio_buttons, function (radio_button: HTMLButtonElement) {
        radio_button.addEventListener("click", function () {
            const layout_name = this.value;
            const current_layout = row.dataset.currentLayout;

            if (layout_name === current_layout) {
                return;
            }

            applyLayout(row, layout_name);
            row.classList.add("shake-widgets");
            const sibling_svg = radio_button.nextElementSibling;
            if (sibling_svg) {
                const field_path = sibling_svg.querySelector(
                    ".dashboard-dropdown-layout-field-path",
                );
                if (!(field_path instanceof Element)) {
                    throw new Error("No element with class dashboard-dropdown-layout-field-path");
                }
                markPathAsSelected(dropdown, field_path);
            }
        });
    });
}

function markPathAsSelected(dropdown: HTMLElement, selected_path_element: Element): void {
    const dropdown_paths = dropdown.querySelectorAll(".dashboard-dropdown-layout-field-path");

    [].forEach.call(dropdown_paths, function (path: HTMLElement) {
        path.classList.remove("selected");
    });
    if (selected_path_element !== null) {
        selected_path_element.classList.add("selected");
    }
}

function hideUnapplicableLayoutsAndCheckCurrentLayout(
    dropdown: HTMLElement,
    nb_columns: number,
    current_layout: string,
): void {
    toggleVisibilityOfTooManyColumnsLayoutText(dropdown, nb_columns);

    const dropdown_items = dropdown.querySelectorAll(".dashboard-dropdown-layout");
    [].forEach.call(dropdown_items, function (dropdown_item: HTMLElement) {
        if (dropdown_item.dataset.layoutName === current_layout) {
            markRadioButtonAsChecked(dropdown_item);
        }
        toggleVisibilityOfDropdownItem(dropdown_item, nb_columns);
    });
}

function markRadioButtonAsChecked(dropdown_item: HTMLElement): void {
    const layout_field = dropdown_item.querySelector(".dashboard-dropdown-layout-field");
    if (!layout_field) {
        throw new Error("No element with class dashboard-dropdown-layout-field");
    }
    const layout_field_path = dropdown_item.querySelector(".dashboard-dropdown-layout-field-path");
    if (!layout_field_path) {
        throw new Error("No element with class dashboard-dropdown-layout-field-path");
    }
    layout_field.setAttribute("checked", "");
    layout_field_path.classList.add("selected");
}

function toggleVisibilityOfDropdownItem(dropdown_item: HTMLElement, nb_columns: number): void {
    const nb_columns_for_layout_data = dropdown_item.dataset.nbColumnsForLayout;
    if (!nb_columns_for_layout_data) {
        throw new Error("No nbColumnsForLayout in data of dropdown");
    }
    const nb_columns_for_layout = parseInt(nb_columns_for_layout_data, 10);

    if (nb_columns_for_layout !== nb_columns) {
        dropdown_item.classList.add("hidden");
    } else {
        dropdown_item.classList.remove("hidden");
    }
}

function toggleVisibilityOfTooManyColumnsLayoutText(
    dropdown: HTMLElement,
    nb_columns: number,
): void {
    const too_many_columns_text = dropdown.querySelector(
        ".dashboard-dropdown-too-many-columns-layout",
    );
    if (!too_many_columns_text) {
        throw new Error("No element with class dashboard-dropdown-too-many-columns-layout");
    }

    if (nb_columns > 3) {
        too_many_columns_text.classList.remove("hidden");
    } else {
        too_many_columns_text.classList.add("hidden");
    }
}

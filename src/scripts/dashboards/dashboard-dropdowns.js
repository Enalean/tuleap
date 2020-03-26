/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

import { dropdown as createDropdown } from "tlp";
import { applyLayout } from "./dashboard-layout.js";

export { init as default, addLayoutDropdown };

function init() {
    var cogs = document.querySelectorAll(
        ".dashboard-widget-actions, #dashboard-tabs-dropdown-trigger"
    );

    [].forEach.call(cogs, function (cog) {
        createDropdown(cog);
    });

    var template_dropdown = document.getElementById("dashboard-layout-dropdown-template");

    if (template_dropdown !== null) {
        initLayoutDropdowns();
    }
}

function initLayoutDropdowns() {
    var all_rows = document.querySelectorAll(".dashboard-widgets-row");

    [].forEach.call(all_rows, function (row) {
        addLayoutDropdown(row);
    });
}

function addLayoutDropdown(row) {
    var template_dropdown = document.getElementById("dashboard-layout-dropdown-template");

    if (template_dropdown === null) {
        return;
    }

    var dropdown_container = cloneLayoutDropdown(row, template_dropdown);
    var dropdown_button = dropdown_container.querySelector(".dashboard-row-dropdown-button");
    initLayoutDropdown(dropdown_button, row);
}

function initLayoutDropdown(dropdown_button, row) {
    var tlp_dropdown = createDropdown(dropdown_button);
    initLayoutChangeButtons(tlp_dropdown.dropdown_menu, row);

    tlp_dropdown.addEventListener("tlp-dropdown-shown", function (event) {
        var current_dropdown = event.detail.target;
        var parent_container = current_dropdown.parentElement;
        var nb_columns = row.querySelectorAll(".dashboard-widgets-column").length;
        var current_layout = row.dataset.currentLayout;

        parent_container.classList.add("shown");
        row.classList.add("shake-widgets");
        hideUnapplicableLayoutsAndCheckCurrentLayout(current_dropdown, nb_columns, current_layout);
    });
    tlp_dropdown.addEventListener("tlp-dropdown-hidden", function (event) {
        var current_dropdown = event.detail.target;
        var parent_container = current_dropdown.parentElement;

        parent_container.classList.remove("shown");
        row.classList.remove("shake-widgets");
    });
}

function cloneLayoutDropdown(row, template_dropdown) {
    var cloned_dropdown = template_dropdown.cloneNode(true);
    cloned_dropdown.removeAttribute("id");

    row.appendChild(cloned_dropdown);

    return cloned_dropdown;
}

function initLayoutChangeButtons(dropdown, row) {
    var radio_buttons = dropdown.querySelectorAll(".dashboard-dropdown-layout-field");

    [].forEach.call(radio_buttons, function (radio_button) {
        radio_button.addEventListener("click", function () {
            var layout_name = this.value;
            var current_layout = row.dataset.currentLayout;

            if (layout_name === current_layout) {
                return;
            }

            applyLayout(row, layout_name);
            row.classList.add("shake-widgets");
            var sibling_svg = radio_button.nextElementSibling;
            if (sibling_svg) {
                markPathAsSelected(
                    dropdown,
                    sibling_svg.querySelector(".dashboard-dropdown-layout-field-path")
                );
            }
        });
    });
}

function markPathAsSelected(dropdown, selected_path_element) {
    var dropdown_paths = dropdown.querySelectorAll(".dashboard-dropdown-layout-field-path");

    [].forEach.call(dropdown_paths, function (path) {
        path.classList.remove("selected");
    });
    if (selected_path_element !== null) {
        selected_path_element.classList.add("selected");
    }
}

function hideUnapplicableLayoutsAndCheckCurrentLayout(dropdown, nb_columns, current_layout) {
    toggleVisibilityOfTooManyColumnsLayoutText(dropdown, nb_columns);

    var dropdown_items = dropdown.querySelectorAll(".dashboard-dropdown-layout");
    [].forEach.call(dropdown_items, function (dropdown_item) {
        if (dropdown_item.dataset.layoutName === current_layout) {
            markRadioButtonAsChecked(dropdown_item, current_layout);
        }
        toggleVisibilityOfDropdownItem(dropdown_item, nb_columns);
    });
}

function markRadioButtonAsChecked(dropdown_item) {
    dropdown_item.querySelector(".dashboard-dropdown-layout-field").setAttribute("checked", "");
    dropdown_item.querySelector(".dashboard-dropdown-layout-field-path").classList.add("selected");
}

function toggleVisibilityOfDropdownItem(dropdown_item, nb_columns) {
    var nb_columns_for_layout = parseInt(dropdown_item.dataset.nbColumnsForLayout, 10);

    if (nb_columns_for_layout !== nb_columns) {
        dropdown_item.classList.add("hidden");
    } else {
        dropdown_item.classList.remove("hidden");
    }
}

function toggleVisibilityOfTooManyColumnsLayoutText(dropdown, nb_columns) {
    var too_many_columns_text = dropdown.querySelector(
        ".dashboard-dropdown-too-many-columns-layout"
    );

    if (nb_columns > 3) {
        too_many_columns_text.classList.remove("hidden");
    } else {
        too_many_columns_text.classList.add("hidden");
    }
}

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

import { dropdown as createDropdown } from 'tlp';
import { applyLayout }                from './dashboard-layout.js';
import { findAncestor }               from './dom-tree-walker.js';

export default init;

function init() {
    var cogs = document.querySelectorAll('.dashboard-widget-actions, #dashboard-tabs-dropdown-trigger');

    [].forEach.call(cogs, function (cog) {
        createDropdown(cog);
    });

    initLayoutDropdowns();
}

function initLayoutDropdowns() {
    var dropdowns = document.querySelectorAll('.dashboard-row-dropdown-button');

    [].forEach.call(dropdowns, function(dropdown) {
        var tlp_dropdown = createDropdown(dropdown);
        initLayoutChangeButtons(tlp_dropdown.dropdown_menu);

        tlp_dropdown.addEventListener('tlp-dropdown-shown', function(event) {
            var current_dropdown = event.detail.target;
            var parent_container = current_dropdown.parentElement;
            var parent_row       = findAncestor(current_dropdown, 'dashboard-widgets-row');
            var nb_columns       = parent_row.querySelectorAll('.dashboard-widgets-column').length;
            var current_layout   = parent_row.dataset.currentLayout;

            parent_container.classList.add('shown');
            parent_row.classList.add('highlight');
            hideUnapplicableLayoutsAndCheckCurrentLayout(current_dropdown, nb_columns, current_layout);
        });
        tlp_dropdown.addEventListener('tlp-dropdown-hidden', function(event) {
            var current_dropdown = event.detail.target;
            var parent_container = current_dropdown.parentElement;
            var parent_row       = findAncestor(current_dropdown, 'dashboard-widgets-row');

            parent_container.classList.remove('shown');
            parent_row.classList.remove('highlight');
        });
    });
}

function initLayoutChangeButtons(dropdown) {
    var radio_buttons = dropdown.querySelectorAll('.dashboard-dropdown-layout-field');

    [].forEach.call(radio_buttons, function(radio_button) {
        radio_button.addEventListener('click', function() {
            var layout_name    = this.value;
            var current_row    = findAncestor(this, 'dashboard-widgets-row');
            var current_layout = current_row.dataset.currentLayout;

            if (layout_name === current_layout) { return; }

            applyLayout(current_row, layout_name);
            current_row.classList.add('highlight');
            var sibling_svg = radio_button.nextElementSibling;
            if (sibling_svg) {
                markPathAsSelected(dropdown, sibling_svg.querySelector('.dashboard-dropdown-layout-field-path'));
            }
        });
    });
}

function markPathAsSelected(dropdown, selected_path_element) {
    var dropdown_paths = dropdown.querySelectorAll('.dashboard-dropdown-layout-field-path');

    [].forEach.call(dropdown_paths, function(path) {
        path.classList.remove('selected');
    });
    if (selected_path_element !== null) {
        selected_path_element.classList.add('selected');
    }
}

function hideUnapplicableLayoutsAndCheckCurrentLayout(dropdown, nb_columns, current_layout) {
    toggleVisibilityOfTooManyColumnsLayoutText(dropdown, nb_columns);

    var dropdown_items = dropdown.querySelectorAll('.dashboard-dropdown-layout');
    [].forEach.call(dropdown_items, function(dropdown_item) {
        if (dropdown_item.dataset.layoutName === current_layout) {
            markRadioButtonAsChecked(dropdown_item, current_layout);
        }
        toggleVisibilityOfDropdownItem(dropdown_item, nb_columns);
    });
}

function markRadioButtonAsChecked(dropdown_item) {
    dropdown_item.querySelector('.dashboard-dropdown-layout-field')
        .setAttribute('checked', '');
    dropdown_item.querySelector('.dashboard-dropdown-layout-field-path')
        .classList.add('selected');
}

function toggleVisibilityOfDropdownItem(dropdown_item, nb_columns) {
    var nb_columns_for_layout = parseInt(dropdown_item.dataset.nbColumnsForLayout, 10);

    if (nb_columns_for_layout !== nb_columns) {
        dropdown_item.classList.add('hidden');
    } else {
        dropdown_item.classList.remove('hidden');
    }
}

function toggleVisibilityOfTooManyColumnsLayoutText(dropdown, nb_columns) {
    var too_many_columns_text = dropdown.querySelector('.dashboard-dropdown-too-many-columns-layout');

    if (nb_columns > 3) {
        too_many_columns_text.classList.remove('hidden');
    } else {
        too_many_columns_text.classList.add('hidden');
    }
}

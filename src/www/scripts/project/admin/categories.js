/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

import { select2 } from "tlp";

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("project-admin-category-form");
    if (!form) {
        return;
    }
    const list_of_multiple_select = document.querySelectorAll(
        ".project-admin-category-select[multiple]"
    );
    for (const select_category of list_of_multiple_select) {
        instantiateSelect2(select_category);
        checkMultipleSelectRespectsMaximumSelectionLength(select_category);
    }
});

function instantiateSelect2(select_category) {
    select2(select_category, {
        placeholder: select_category.dataset.placeholder,
        allowClear: true,
        maximumSelectionLength: select_category.dataset.maximumSelectionLength,
        templateSelection: (state) => {
            if (!state.element) {
                return state.text;
            }

            return state.element.dataset.label;
        },
    }).on("change", function () {
        checkMultipleSelectRespectsMaximumSelectionLength(select_category);
    });
}

function checkMultipleSelectRespectsMaximumSelectionLength(select_category) {
    let nb_selected_values = 0;
    const n = select_category.options.length;
    for (let i = 0; i < n; i++) {
        if (select_category.options[i].selected) {
            nb_selected_values++;
        }
    }

    if (nb_selected_values > select_category.dataset.maximumSelectionLength) {
        select_category.setCustomValidity(select_category.dataset.maximumSelectionLengthMessage);
        select_category.closest(".tlp-form-element").classList.add("tlp-form-element-error");
    } else {
        select_category.setCustomValidity("");
        select_category.closest(".tlp-form-element").classList.remove("tlp-form-element-error");
    }
}

/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import type { DataFormat, GroupedDataFormat } from "tlp";
import { select2 } from "tlp";

interface ProjectCategory extends DataFormat {
    text: string;
    element: HTMLOptionElement;
}

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("project-admin-category-form");
    if (!form) {
        return;
    }
    const list_of_multiple_select = document.querySelectorAll(
        ".project-admin-category-select[multiple]",
    );
    for (const select_category of list_of_multiple_select) {
        if (!(select_category instanceof HTMLSelectElement)) {
            continue;
        }
        instantiateSelect2(select_category);
        checkMultipleSelectRespectsMaximumSelectionLength(select_category);
    }
});

function instantiateSelect2(select_category: HTMLSelectElement): void {
    let max_selection_length;
    if (select_category.dataset.maximumSelectionLength) {
        max_selection_length = parseInt(select_category.dataset.maximumSelectionLength, 10);
    }
    select2(select_category, {
        placeholder: select_category.dataset.placeholder,
        allowClear: true,
        maximumSelectionLength: max_selection_length,
        templateSelection: (state: DataFormat | GroupedDataFormat | ProjectCategory) => {
            if (
                "element" in state &&
                state.element !== undefined &&
                state.element.dataset.label !== undefined
            ) {
                return state.element.dataset.label;
            }

            return state.text;
        },
    });
    select_category.addEventListener("change", function () {
        checkMultipleSelectRespectsMaximumSelectionLength(select_category);
    });
}

function checkMultipleSelectRespectsMaximumSelectionLength(
    select_category: HTMLSelectElement,
): void {
    let nb_selected_values = 0;
    const n = select_category.options.length;
    for (let i = 0; i < n; i++) {
        if (select_category.options[i].selected) {
            nb_selected_values++;
        }
    }
    const max_selection_length = select_category.dataset.maximumSelectionLength;
    if (!max_selection_length) {
        throw new Error("No maximum selection length");
    }
    const max_selection_length_message = select_category.dataset.maximumSelectionLengthMessage;
    if (!max_selection_length_message) {
        throw new Error("No maximum selection length message");
    }
    const tlp_form_element = select_category.closest(".tlp-form-element");
    if (!tlp_form_element) {
        throw new Error("No TLP Form Element");
    }

    if (nb_selected_values > parseInt(max_selection_length, 10)) {
        select_category.setCustomValidity(max_selection_length_message);
        tlp_form_element.classList.add("tlp-form-element-error");
    } else {
        select_category.setCustomValidity("");
        tlp_form_element.classList.remove("tlp-form-element-error");
    }
}

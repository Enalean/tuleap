/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

import { createModal } from "tlp";

document.addEventListener("DOMContentLoaded", function () {
    const modal_add_element = document.getElementById("trove-cat-add"),
        button_modal_add_element = document.getElementById("add-project-category-button");

    if (modal_add_element && button_modal_add_element) {
        const modal_add = createModal(modal_add_element);

        button_modal_add_element.addEventListener("click", function () {
            modal_add.toggle();
        });
    }

    const matching_buttons = document.querySelectorAll(
        ".trovecats-edit-button, .trovecats-delete-button",
    );

    for (const button of matching_buttons) {
        if (!(button instanceof HTMLElement)) {
            throw Error("Button is not HTMLElement");
        }

        if (!button.dataset.modalId) {
            throw new Error("Modal id is not in dataset");
        }

        const modal_element = document.getElementById(button.dataset.modalId);

        if (modal_element) {
            const modal = createModal(modal_element);

            button.addEventListener("click", function () {
                modal.toggle();
            });
        }
    }

    selectParentCategoryOption();
    bindNbMaxValuesToProjectFlag();
});

function bindNbMaxValuesToProjectFlag(): void {
    const all_nb_max_input_elements = document.querySelectorAll(".trove-cats-nb-max-values-input");
    for (const nb_max_input of all_nb_max_input_elements) {
        if (!(nb_max_input instanceof HTMLInputElement)) {
            throw Error("Nb max input is not HTMLElement");
        }
        if (!nb_max_input.dataset.inputProjectFlagId) {
            throw new Error("InputProjectFlagId is not in dataset");
        }
        const is_project_flag_input = document.getElementById(
            nb_max_input.dataset.inputProjectFlagId,
        );
        if (!(is_project_flag_input instanceof HTMLInputElement)) {
            continue;
        }

        if (is_project_flag_input.checked) {
            markFormElementAsDisabled(nb_max_input);
        } else if (nb_max_input.value !== "1") {
            markFormElementAsDisabled(is_project_flag_input);
        }

        is_project_flag_input.addEventListener("click", () => {
            if (is_project_flag_input.checked) {
                markFormElementAsDisabled(nb_max_input);
            } else {
                markFormElementAsEnabled(nb_max_input);
            }
        });

        ["change", "keyup"].forEach(function (event_type) {
            nb_max_input.addEventListener(event_type, () => {
                if (nb_max_input.value === "1") {
                    markFormElementAsEnabled(is_project_flag_input);
                } else {
                    markFormElementAsDisabled(is_project_flag_input);
                }
            });
        });
    }
}

function markFormElementAsDisabled(input: HTMLInputElement): void {
    input.disabled = true;
    const parent = input.closest(".tlp-form-element");
    if (parent) {
        parent.classList.add("tlp-form-element-disabled");
    }
    if (!input.dataset.warningId) {
        return;
    }
    const warning = document.getElementById(input.dataset.warningId);
    if (warning) {
        warning.classList.add("shown");
    }
}

function markFormElementAsEnabled(input: HTMLInputElement): void {
    if (input.dataset.isPermanentlyDisabled && Boolean(input.dataset.isPermanentlyDisabled)) {
        return;
    }

    input.disabled = false;
    const parent = input.closest(".tlp-form-element");
    if (parent) {
        parent.classList.remove("tlp-form-element-disabled");
    }
    if (!input.dataset.warningId) {
        return;
    }
    const warning = document.getElementById(input.dataset.warningId);
    if (warning) {
        warning.classList.remove("shown");
    }
}

function selectParentCategoryOption(): void {
    const select_categories = document.getElementsByClassName(
        "trove-cats-modal-select-parent-category",
    );

    for (const select_category of select_categories) {
        if (!(select_category instanceof HTMLSelectElement)) {
            throw Error("Select Category is not HTMLSelectElement");
        }
        preselectOption(select_category);
        listenChangeEvent(select_category);
    }
}

function preselectOption(select_category: HTMLSelectElement): void {
    const id = select_category.dataset.id;
    if (!id) {
        throw new Error("Id is not in dataset of SelectCategory");
    }
    const parent_id = select_category.dataset.parentTroveId;
    let is_parent_hidden = false;
    const length = select_category.options.length;
    for (let i = 0; i < length; i++) {
        const option = select_category.options[i];
        const is_option_at_root_level = Boolean(option.dataset.isTopLevelId);
        if (is_option_at_root_level || parent_id === option.dataset.parentId) {
            is_parent_hidden = false;
        }

        if (parent_id === option.value) {
            option.selected = true;
            allowMandatoryPropertyOnlyForRootCategories(parent_id, id);
            changeVisibilityOfDisplayAtProjectCreation(id, option);
        }

        is_parent_hidden = hideChildren(id, option, is_parent_hidden);
    }
}

function listenChangeEvent(select_category: HTMLSelectElement): void {
    const id = select_category.dataset.id;
    if (!id) {
        throw new Error("Id is not in dataset of SelectCategory");
    }
    select_category.addEventListener("change", () => {
        const option = select_category.options[select_category.selectedIndex];
        allowMandatoryPropertyOnlyForRootCategories(option.value, id);
        changeVisibilityOfDisplayAtProjectCreation(id, option);
    });
}

function hideChildren(id: string, option: HTMLOptionElement, is_parent_hidden: boolean): boolean {
    if (is_parent_hidden || id === option.value) {
        option.classList.add("trove-cats-option-hidden");
        option.disabled = true;
        return true;
    }

    return false;
}

function allowMandatoryPropertyOnlyForRootCategories(select_id: string, id: string): void {
    const mandatory_element = document.getElementById("is-mandatory-" + id);
    const mandatory_checkbox = document.getElementById("trove-cats-modal-mandatory-checkbox-" + id);
    if (!(mandatory_element instanceof HTMLInputElement)) {
        throw new Error("MandatoryElement is not a HTMLInputElement");
    }
    if (!(mandatory_checkbox instanceof HTMLElement)) {
        throw new Error("MandatoryCheckbox is not a HTMLElement");
    }

    if (select_id !== "0") {
        mandatory_checkbox.classList.add("tlp-form-element-disabled");
        mandatory_element.disabled = true;
        mandatory_element.checked = false;
    } else {
        mandatory_element.disabled = false;
        mandatory_checkbox.classList.remove("tlp-form-element-disabled");
    }
}

function changeVisibilityOfDisplayAtProjectCreation(
    id: string,
    selected_option: HTMLOptionElement,
): void {
    const checkbox = document.getElementById("trove-cats-modal-display-at-project-creation-" + id);
    const form_element = document.getElementById(
        "trove-cats-modal-display-at-project-creation-form-element-" + id,
    );
    if (!(checkbox instanceof HTMLInputElement)) {
        throw new Error("Checkbox is not a HTMLInputElement");
    }
    if (!(form_element instanceof HTMLElement)) {
        throw new Error("FomElement is not a HTMLElement");
    }
    if (!selected_option.dataset.isTopLevelId || !selected_option.dataset.isParentMandatory) {
        form_element.classList.add("tlp-form-element-disabled");
        checkbox.disabled = true;
        checkbox.checked = false;
    } else {
        checkbox.disabled = false;
        form_element.classList.remove("tlp-form-element-disabled");
    }
}

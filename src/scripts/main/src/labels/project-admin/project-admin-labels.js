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

import { createModal, select2 } from "tlp";
import { sprintf } from "sprintf-js";
import { filterInlineTable } from "@tuleap/filter-inline-table";

document.addEventListener("DOMContentLoaded", () => {
    const labels_table = document.getElementById("project-labels-table");
    if (!labels_table) {
        return;
    }

    initColorSelectors();

    const filter = document.getElementById("project-labels-table-filter");
    if (filter) {
        filterInlineTable(filter);
    }

    const buttons = document.querySelectorAll(
        ".project-labels-table-delete-button, .project-labels-table-edit-button, .project-labels-table-add-button",
    );
    for (const button of buttons) {
        const modal_element = document.getElementById(button.dataset.modalId);

        if (modal_element) {
            const modal = createModal(modal_element);
            const edit_name_input = modal_element.querySelector(".project-label-edit-name");

            button.addEventListener("click", () => {
                if (edit_name_input) {
                    hideWarning(edit_name_input);
                    edit_name_input.value = edit_name_input.dataset.originalValue;
                }

                modal.show();
            });

            if (edit_name_input) {
                modal.addEventListener("tlp-modal-hidden", () => {
                    modal_element.reset();
                    // force select2 to display the current color
                    modal_element
                        .querySelector(".project-label-color-selector")
                        .dispatchEvent(new Event("change"));
                });
            }
        }
    }

    const existing_labels = JSON.parse(labels_table.dataset.existingLabelsNames).map((label) =>
        label.toLowerCase(),
    );
    for (const input of document.querySelectorAll(".project-label-edit-name")) {
        input.addEventListener("input", onLabelChange);
    }

    let timer;
    function onLabelChange() {
        if (timer) {
            clearTimeout(timer);
        }

        timer = setTimeout(() => {
            if (existing_labels.indexOf(this.value.toLowerCase()) === -1) {
                hideWarning(this);
            } else if (this.value !== this.dataset.originalValue) {
                showWarning(this);
            }
        }, 150);
    }

    function hideWarning(input) {
        document
            .getElementById(input.dataset.targetCancelId)
            .classList.remove("tlp-button-warning");
        document.getElementById(input.dataset.targetWarningId).classList.remove("shown");

        const save = document.getElementById(input.dataset.targetSaveId);
        save.classList.remove("tlp-button-warning");
        save.disabled = false;
    }

    function showWarning(input) {
        document.getElementById(input.dataset.targetCancelId).classList.add("tlp-button-warning");

        const save = document.getElementById(input.dataset.targetSaveId);
        save.classList.add("tlp-button-warning");
        if (!JSON.parse(input.dataset.isSaveAllowedOnDuplicate)) {
            save.disabled = true;
        }

        const warning = document.getElementById(input.dataset.targetWarningId);
        warning.classList.add("shown");
        warning.textContent = sprintf(input.dataset.warningMessage, input.value);
    }

    function formatOptionColor({ id }) {
        const element = document.createElement("span");
        element.classList.add(id);

        return element;
    }

    function initColorSelectors() {
        for (const color_selector of document.querySelectorAll(".project-label-color-selector")) {
            select2(color_selector, {
                containerCssClass: "project-label-color-container",
                dropdownCssClass: "project-label-color-results",
                minimumResultsForSearch: Infinity,
                templateResult: formatOptionColor,
                templateSelection: formatOptionColor,
            });
        }
    }
});

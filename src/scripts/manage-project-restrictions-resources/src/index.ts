/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { filterInlineTable } from "@tuleap/filter-inline-table";
import { autocomplete_projects_for_select2 } from "@tuleap/autocomplete-for-select2";

function bindAllowAllEvent(): void {
    const switch_button = document.getElementById("allowed-projects-all-allowed");
    if (!(switch_button instanceof HTMLInputElement)) {
        return;
    }
    const restrict_modal_id = switch_button.dataset.targetRestrictModalId;
    const allow_modal_id = switch_button.dataset.targetAllowModalId;
    const form = switch_button.form;

    if (!form) {
        return;
    }

    let allow_modal: Modal | null = null;
    if (allow_modal_id) {
        const modal_element = document.getElementById(allow_modal_id);
        if (!modal_element) {
            throw Error("Unable to find confirmation allow modal " + allow_modal_id);
        }
        allow_modal = createModal(modal_element);
        allow_modal.addEventListener("tlp-modal-hidden", function () {
            form.reset();
        });
    }
    let restrict_modal: Modal | null = null;
    if (restrict_modal_id) {
        const modal_element = document.getElementById(restrict_modal_id);
        if (!modal_element) {
            throw Error("Unable to find confirmation restrict modal " + restrict_modal_id);
        }

        restrict_modal = createModal(modal_element);
        restrict_modal.addEventListener("tlp-modal-hidden", function () {
            form.reset();
        });
    }

    switch_button.addEventListener("change", function (): void {
        if (switch_button.checked && allow_modal) {
            allow_modal.show();
        } else if (!switch_button.checked && restrict_modal) {
            restrict_modal.show();
        } else {
            form.submit();
        }
    });
}

function bindFilterEvent(): void {
    const filter = document.getElementById("filter-projects");
    if (filter instanceof HTMLInputElement) {
        filterInlineTable(filter);
    }
}

function bindCheckboxesEvent(): void {
    const select_all_checkbox = document.getElementById("check-all");

    if (!(select_all_checkbox instanceof HTMLInputElement)) {
        return;
    }

    const checkboxes: NodeListOf<HTMLInputElement> = document.querySelectorAll(
        '#allowed-projects-list input[type="checkbox"]:not(#check-all)',
    );

    (function toggleAll(): void {
        select_all_checkbox.addEventListener("change", function () {
            if (select_all_checkbox?.checked) {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = true;
                });
            } else {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = false;
                });
            }
            toggleRevokeSelectedButton();
        });
    })();

    (function projectCheckboxesEvent(): void {
        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener("change", function () {
                select_all_checkbox.checked = false;
                toggleRevokeSelectedButton();
            });
        });
    })();

    function toggleRevokeSelectedButton(): void {
        const revoke_project_button = document.getElementById("revoke-project");

        if (!(revoke_project_button instanceof HTMLButtonElement)) {
            throw new Error("Cannot find button with ID revoke-project");
        }

        const checked_checkboxes = document.querySelectorAll(
            '#allowed-projects-list input[type="checkbox"]:not(#check-all):checked',
        );

        revoke_project_button.disabled = checked_checkboxes.length <= 0;
    }
}

function bindDeleteEvent(): void {
    const dom_natures_modal_create = document.getElementById("revoke-modal");

    if (dom_natures_modal_create) {
        const tlp_natures_modal_create = createModal(dom_natures_modal_create);

        document.getElementById("revoke-project")?.addEventListener("click", function (): void {
            tlp_natures_modal_create.toggle();
        });

        document.getElementById("revoke-confirm")?.addEventListener("click", function (): void {
            const form = document.getElementById("projects-allowed-form");
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            const input = document.createElement("input");
            input.type = "hidden";
            input.name = "revoke-project";
            input.value = "1";
            form.appendChild(input);

            form.submit();
        });
    }
}

function projectAutocompleter(): void {
    const autocompleter = document.getElementById("project-to-allow");

    if (autocompleter) {
        autocomplete_projects_for_select2(autocompleter, {
            include_private_projects: true,
        });
    }
}

document.addEventListener("DOMContentLoaded", () => {
    bindAllowAllEvent();
    bindFilterEvent();
    bindCheckboxesEvent();
    bindDeleteEvent();
    projectAutocompleter();
});

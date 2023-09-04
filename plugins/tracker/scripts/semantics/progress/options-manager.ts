/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

export function init(
    update_semantic_progress_button: HTMLElement,
    computation_method_selector: HTMLSelectElement,
    effort_based_config_section: HTMLElement,
    total_effort_selector: HTMLSelectElement,
    remaining_effort_selector: HTMLSelectElement,
    links_count_based_config_section: HTMLElement,
): void {
    toggleComputationMethodConfigSection(
        update_semantic_progress_button,
        computation_method_selector,
        effort_based_config_section,
        total_effort_selector,
        remaining_effort_selector,
        links_count_based_config_section,
    );
    disableAlreadySelectedOptions(total_effort_selector, remaining_effort_selector);
    disableAlreadySelectedOptions(remaining_effort_selector, total_effort_selector);

    computation_method_selector.addEventListener("change", () => {
        toggleComputationMethodConfigSection(
            update_semantic_progress_button,
            computation_method_selector,
            effort_based_config_section,
            total_effort_selector,
            remaining_effort_selector,
            links_count_based_config_section,
        );
    });

    total_effort_selector.addEventListener("change", () => {
        disableAlreadySelectedOptions(total_effort_selector, remaining_effort_selector);
    });

    remaining_effort_selector.addEventListener("change", () => {
        disableAlreadySelectedOptions(remaining_effort_selector, total_effort_selector);
    });
}

function disableAlreadySelectedOptions(
    current_selectbox: HTMLSelectElement,
    target_selectbox: HTMLSelectElement,
): void {
    const current_selectbox_value = current_selectbox.value;

    enableAllOptions(target_selectbox);

    for (const option of target_selectbox.options) {
        const option_value = option.value;
        if (option_value && option_value === current_selectbox_value) {
            option.disabled = true;
        }
    }
}

function enableAllOptions(selectbox: HTMLSelectElement): void {
    for (const option of selectbox.options) {
        option.disabled = false;
    }
}

function toggleComputationMethodConfigSection(
    update_semantic_progress_button: HTMLElement,
    computation_method_selector: HTMLSelectElement,
    effort_based_config_section: HTMLElement,
    total_effort_selector: HTMLSelectElement,
    remaining_effort_selector: HTMLSelectElement,
    links_count_based_config_section: HTMLElement,
): void {
    const selected_method = computation_method_selector.value;
    toggleEffortBasedConfigSection(
        selected_method,
        effort_based_config_section,
        total_effort_selector,
        remaining_effort_selector,
        update_semantic_progress_button,
    );

    toggleLinksCountBasedConfigSection(
        selected_method,
        links_count_based_config_section,
        update_semantic_progress_button,
    );
}

function toggleEffortBasedConfigSection(
    selected_method: string,
    effort_based_config_section: HTMLElement,
    total_effort_selector: HTMLSelectElement,
    remaining_effort_selector: HTMLSelectElement,
    update_semantic_progress_button: HTMLElement,
): void {
    if (selected_method !== "effort-based") {
        total_effort_selector.setAttribute("disabled", "disabled");
        remaining_effort_selector.setAttribute("disabled", "disabled");
        effort_based_config_section.classList.remove("selected-computation-method-config");
        return;
    }

    total_effort_selector.removeAttribute("disabled");
    remaining_effort_selector.removeAttribute("disabled");
    update_semantic_progress_button.removeAttribute("disabled");
    effort_based_config_section.classList.add("selected-computation-method-config");
}

function toggleLinksCountBasedConfigSection(
    selected_method: string,
    links_count_based_config_section: HTMLElement,
    update_semantic_progress_button: HTMLElement,
): void {
    if (selected_method !== "artifacts-links-count-based") {
        links_count_based_config_section.classList.remove("selected-computation-method-config");
        return;
    }

    if (
        links_count_based_config_section.classList.contains("links-count-based-config-impossible")
    ) {
        update_semantic_progress_button.setAttribute("disabled", "disabled");
    }

    links_count_based_config_section.classList.add("selected-computation-method-config");
}

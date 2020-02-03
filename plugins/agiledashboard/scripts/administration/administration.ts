/*
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

import { createPopover, modal as createModal } from "tlp";

document.addEventListener("DOMContentLoaded", () => {
    canNotCreatePlanningPopover();
    removePlanningButton();
    displayButtonSaveWithModalWhenSwitchHasBeenClickedAtLeastOnce();
});

function canNotCreatePlanningPopover(): void {
    const trigger = document.getElementById(
        "agiledashboard-administration-cannot-create-planning-popover-trigger"
    );
    if (!trigger) {
        return;
    }

    const popover = document.getElementById(
        "agiledashboard-administration-cannot-create-planning-popover"
    );
    if (!popover) {
        return;
    }
    createPopover(trigger, popover);
}

function removePlanningButton(): void {
    const button = document.getElementById("agiledashboard-administration-remove-planning-button");

    if (button && button.dataset) {
        const modal_target_id = button.dataset.targetModalId;

        if (!modal_target_id) {
            return;
        }

        const modal_element = document.getElementById(modal_target_id);
        if (!modal_element) {
            return;
        }
        const modal = createModal(modal_element);

        button.addEventListener("click", () => {
            modal.show();
        });
    }
}

export function displayButtonSaveWithModalWhenSwitchHasBeenClickedAtLeastOnce(): void {
    const explicit_backlog_usage_button = document.getElementById("ad-service-submit");
    if (
        !explicit_backlog_usage_button ||
        !explicit_backlog_usage_button.dataset.canUseExplicitBacklog
    ) {
        return;
    }

    const explicit_backlog_usage_button_with_modal = document.getElementById(
        "scrum-configuration-edit-options-button"
    );
    if (
        !explicit_backlog_usage_button_with_modal ||
        !explicit_backlog_usage_button_with_modal.dataset ||
        !explicit_backlog_usage_button_with_modal.dataset.targetModal
    ) {
        return;
    }

    const explicit_backlog_usage_switch = document.getElementById("use-explicit-top-backlog");
    if (!explicit_backlog_usage_switch || !explicit_backlog_usage_button_with_modal.dataset) {
        return;
    }

    explicit_backlog_usage_switch.addEventListener("click", () => {
        if (explicit_backlog_usage_button_with_modal.dataset.explicitBacklogValue === "1") {
            explicit_backlog_usage_button_with_modal.dataset.explicitBacklogValue = "0";
        } else {
            explicit_backlog_usage_button_with_modal.dataset.explicitBacklogValue = "1";
        }

        explicit_backlog_usage_button.classList.add("scrum-administration-submit-hidden");
        explicit_backlog_usage_button_with_modal.classList.remove(
            "scrum-administration-submit-hidden"
        );
    });

    addModalListeners(explicit_backlog_usage_button_with_modal);
}

export function addModalListeners(explicit_backlog_usage_button_with_modal: HTMLElement): void {
    if (
        !explicit_backlog_usage_button_with_modal ||
        !explicit_backlog_usage_button_with_modal.dataset ||
        !explicit_backlog_usage_button_with_modal.dataset.targetModal
    ) {
        return;
    }

    const modal_element = document.getElementById(
        explicit_backlog_usage_button_with_modal.dataset.targetModal
    );
    if (!modal_element) {
        return;
    }

    const modal = createModal(modal_element);

    const legacy_mode_text = document.getElementById("legacy-mode-text");
    const explicit_mode_text = document.getElementById("explicit-mode-text");
    if (!legacy_mode_text || !explicit_mode_text) {
        return;
    }

    explicit_backlog_usage_button_with_modal.addEventListener("click", () => {
        const explicit_backlog_usage_button_with_modal = document.getElementById(
            "scrum-configuration-edit-options-button"
        );

        if (
            !explicit_backlog_usage_button_with_modal ||
            !explicit_backlog_usage_button_with_modal.dataset
        ) {
            return;
        }

        if (explicit_backlog_usage_button_with_modal.dataset.explicitBacklogValue === "1") {
            legacy_mode_text.classList.add("scrum-administration-submit-hidden");
            explicit_mode_text.classList.remove("scrum-administration-submit-hidden");
        } else {
            legacy_mode_text.classList.remove("scrum-administration-submit-hidden");
            explicit_mode_text.classList.add("scrum-administration-submit-hidden");
        }

        modal.show();
    });
}

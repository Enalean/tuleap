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

import { createPopover } from "@tuleap/tlp-popovers";
import { openTargetModalIdOnClick } from "@tuleap/tlp-modal";

document.addEventListener("DOMContentLoaded", () => {
    canNotCreatePlanningPopover();
    removePlanningButton();
    displayButtonSaveWithModalWhenSwitchHasBeenClickedAtLeastOnce();
});

function canNotCreatePlanningPopover(): void {
    const trigger = document.getElementById(
        "agiledashboard-administration-cannot-create-planning-popover-trigger",
    );
    if (!trigger) {
        return;
    }

    const popover = document.getElementById(
        "agiledashboard-administration-cannot-create-planning-popover",
    );
    if (!popover) {
        return;
    }
    createPopover(trigger, popover);
}

const REMOVE_PLANNING_BUTTON_ID = "agiledashboard-administration-remove-planning-button";

function removePlanningButton(): void {
    const remove_planning_button = document.getElementById(REMOVE_PLANNING_BUTTON_ID);
    if (remove_planning_button !== null && remove_planning_button.classList.contains("disabled")) {
        return;
    }
    openTargetModalIdOnClick(document, REMOVE_PLANNING_BUTTON_ID);
}

export function displayButtonSaveWithModalWhenSwitchHasBeenClickedAtLeastOnce(): void {
    const submit_button = document.getElementById("ad-service-submit");

    if (!submit_button) {
        return;
    }

    const explicit_backlog_usage_button_with_modal = document.getElementById(
        "scrum-configuration-edit-options-button",
    );
    if (
        !explicit_backlog_usage_button_with_modal ||
        !explicit_backlog_usage_button_with_modal.dataset
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

        submit_button.classList.add("scrum-administration-submit-hidden");
        explicit_backlog_usage_button_with_modal.classList.remove(
            "scrum-administration-submit-hidden",
        );
    });

    addModalListeners(explicit_backlog_usage_button_with_modal);
}

export function addModalListeners(explicit_backlog_usage_button_with_modal: HTMLElement): void {
    const legacy_mode_text = document.getElementById("legacy-mode-text");
    const explicit_mode_text = document.getElementById("explicit-mode-text");
    if (!legacy_mode_text || !explicit_mode_text) {
        return;
    }

    openTargetModalIdOnClick(
        document,
        explicit_backlog_usage_button_with_modal.id,
        (clicked_button: HTMLElement) => {
            if (clicked_button.dataset.explicitBacklogValue === "1") {
                legacy_mode_text.classList.add("scrum-administration-submit-hidden");
                explicit_mode_text.classList.remove("scrum-administration-submit-hidden");
            } else {
                legacy_mode_text.classList.remove("scrum-administration-submit-hidden");
                explicit_mode_text.classList.add("scrum-administration-submit-hidden");
            }
        },
    );
}

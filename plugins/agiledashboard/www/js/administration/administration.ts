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

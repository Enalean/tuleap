/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { createPopover } from "tlp";

document.addEventListener("DOMContentLoaded", () => {
    handleTrackerStatisticsPopovers();
    handleTrackerDeletion();
});

function handleTrackerStatisticsPopovers(): void {
    for (const trigger of document.querySelectorAll(".trackers-homepage-tracker")) {
        if (!(trigger instanceof HTMLElement)) {
            continue;
        }

        const popover_content = document.getElementById(
            "tracker-statistics-popover-" + trigger.dataset.trackerId
        );
        if (popover_content === null) {
            throw new Error(
                `Statistics popover not found for tracker #${trigger.dataset.trackerId}`
            );
        }

        createPopover(trigger, popover_content, {
            placement: "right",
        });
    }
}

function handleTrackerDeletion(): void {
    for (const trash of document.querySelectorAll(".trackers-homepage-tracker-trash")) {
        trash.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (
                trash instanceof HTMLFormElement &&
                // eslint-disable-next-line no-alert
                confirm("Do you want to delete this tracker?")
            ) {
                trash.submit();
            }
        });
    }
}

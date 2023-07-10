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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

import "./generate-pie-charts";

document.addEventListener("DOMContentLoaded", () => {
    const admin_homepage_queues = document.querySelectorAll(
        ".siteadmin-homepage-system-events-queue"
    );
    for (const admin_homepage_queue of admin_homepage_queues) {
        admin_homepage_queue.addEventListener("click", (event) => {
            if (
                !event.target ||
                !(event.target instanceof HTMLElement) ||
                !event.target.classList
            ) {
                throw new Error("Event target is not found or is not a DOM element");
            }

            if (!event.target.parentNode || !(event.target.parentNode instanceof HTMLElement)) {
                throw new Error("Event target parent node is not found or is not a DOM element");
            }

            if (!(admin_homepage_queue instanceof HTMLElement)) {
                throw new Error("admin_homepage_queue does not have a dataset");
            }

            if (
                !event.target.classList.contains("system-event-type-count") &&
                !event.target.parentNode.classList.contains("system-event-type-count")
            ) {
                window.location.href = window.location.origin + admin_homepage_queue.dataset.href;
            }
        });
    }
});

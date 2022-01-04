/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

import { patch } from "tlp";

export { init };

function init(): void {
    const sidebar_collapsers = document.querySelectorAll("[data-sidebar-collapser]"),
        sidebar = document.querySelector(".sidebar");

    bindSidebarEvent();

    function bindSidebarEvent(): void {
        if (!sidebar) {
            return;
        }
        sidebar.addEventListener("click", (event) => {
            const clicked_element = event.target;
            if (!clicked_element || !(clicked_element instanceof HTMLElement)) {
                return;
            }

            const is_clicked_element_a_sidebar_collapser =
                isClickedElementASidebarCollapser(clicked_element);
            const sidebar_collapsed_class = clicked_element.dataset.collapsedClass;

            if (!is_clicked_element_a_sidebar_collapser || !sidebar_collapsed_class) {
                return;
            }

            if (document.body.classList.contains(sidebar_collapsed_class)) {
                document.body.classList.remove(sidebar_collapsed_class);
                updateSidebarStateUserPreference("sidebar-expanded");
            } else {
                document.body.classList.remove("sidebar-expanded", sidebar_collapsed_class);
                document.body.classList.add(sidebar_collapsed_class);
                updateSidebarStateUserPreference(sidebar_collapsed_class);
            }
        });
    }

    function isClickedElementASidebarCollapser(clicked_element: HTMLElement): boolean {
        let is_clicked_element_a_sidebar_collapser = false;

        for (const sidebar_collapser of sidebar_collapsers) {
            if (sidebar_collapser === clicked_element) {
                is_clicked_element_a_sidebar_collapser = true;
            }
        }

        return is_clicked_element_a_sidebar_collapser;
    }
}

function updateSidebarStateUserPreference(state: string): void {
    patch("/api/users/self/preferences", {
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            key: "sidebar_state",
            value: state,
        }),
    });
}

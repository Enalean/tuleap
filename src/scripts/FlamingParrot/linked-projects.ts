/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

export function init(): void {
    const linked_projects_element = document.getElementById("project-sidebar-linked-projects");

    if (!linked_projects_element) {
        return;
    }

    const popover_element = document.getElementById("project-sidebar-linked-projects-popover");

    if (!popover_element) {
        return;
    }

    createPopover(linked_projects_element, popover_element, { placement: "right-start" });
}

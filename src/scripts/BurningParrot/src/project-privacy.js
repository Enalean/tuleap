/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

export function init() {
    handleBreadcrumbPrivacyPopover();
    handleSidebarPrivacyPopover();
}

function handleBreadcrumbPrivacyPopover() {
    const project_privacy_element = document.getElementById("breadcrumb-project-privacy-icon");
    if (!project_privacy_element) {
        return;
    }

    project_privacy_element.title = "";

    const popover_content = document.getElementById("current-project-nav-title-popover");

    createPopover(project_privacy_element, popover_content, {
        anchor: project_privacy_element,
        placement: "bottom-start",
    });
}

function handleSidebarPrivacyPopover() {
    const project_privacy_element = document.querySelector(
        ".project-sidebar-title > .project-sidebar-title-icon"
    );
    if (!project_privacy_element) {
        return;
    }

    project_privacy_element.title = "";

    const popover_content = document.getElementById("project-sidebar-nav-title-popover");

    createPopover(project_privacy_element, popover_content, {
        anchor: document.querySelector(".project-sidebar-header"),
        placement: "right-start",
    });
}

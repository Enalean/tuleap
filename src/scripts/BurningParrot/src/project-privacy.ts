/**
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

import { createPopover } from "tlp";

export function init(): void {
    handleBreadcrumbPrivacyPopover();
    handleSidebarPrivacyPopover();
}

function handleBreadcrumbPrivacyPopover(): void {
    const project_privacy_element = document.getElementById("breadcrumb-project-privacy-icon");
    if (!project_privacy_element) {
        return;
    }

    project_privacy_element.title = "";

    const popover_content = document.getElementById("current-project-nav-title-popover");

    if (!popover_content) {
        return;
    }

    createPopover(project_privacy_element, popover_content, {
        anchor: project_privacy_element,
        placement: "bottom-start",
    });
}

function handleSidebarPrivacyPopover(): void {
    const project_privacy_element = document.querySelector("[data-project-privacy-icon]");
    if (!project_privacy_element || !(project_privacy_element instanceof HTMLElement)) {
        return;
    }

    project_privacy_element.title = "";

    const popover_content = document.getElementById("project-sidebar-nav-title-popover");

    if (!popover_content) {
        return;
    }

    const project_sidebar_header = document.querySelector(".project-sidebar-header-name");

    if (!project_sidebar_header || !(project_sidebar_header instanceof HTMLElement)) {
        return;
    }

    createPopover(project_privacy_element, popover_content, {
        anchor: project_sidebar_header,
        placement: "right-start",
    });
}

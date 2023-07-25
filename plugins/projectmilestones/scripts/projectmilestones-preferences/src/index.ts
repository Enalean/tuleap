/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { autocomplete_projects_for_select2 } from "@tuleap/autocomplete-for-select2";

document.addEventListener("dashboard-edit-widget-modal-content-loaded", (event) => {
    if (!(event instanceof CustomEvent)) {
        return;
    }

    initProjectsForSelect2(event);
});

document.addEventListener("dashboard-add-widget-settings-loaded", (event) => {
    if (!(event instanceof CustomEvent)) {
        return;
    }

    initProjectsForSelect2(event);
});

function initProjectsForSelect2(event: CustomEvent): void {
    const container = event.detail.target.querySelector("#select-project-milestones-widget");

    if (!container) {
        return;
    }

    autocomplete_projects_for_select2(container, { include_private_projects: true });
}

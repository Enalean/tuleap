/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

export {}; // force the current script to be a module since it has no import

document.addEventListener("dashboard-edit-widget-modal-content-loaded", (event: Event) =>
    initRoadmapConfigurationForm(event, false),
);

document.addEventListener("dashboard-add-widget-settings-loaded", (event: Event) => {
    initRoadmapConfigurationForm(event, true);
});

async function initRoadmapConfigurationForm(event: Event, is_in_creation: boolean): Promise<void> {
    if (!(event instanceof CustomEvent)) {
        return;
    }

    const mount_point = event.detail.target.querySelector(".configure-roadmap-widget-mount-point");
    if (!(mount_point instanceof HTMLElement)) {
        return;
    }

    // Mount the Vue app with dynamic import so that we don't load the full app
    // on every requests, only when the user wants to configure it.
    const { bootstrapVueMountPoint } = await import(
        /* webpackChunkName: "bootstrap-roadmap-widget" */ "./bootstrap-vue-mount-point"
    );
    bootstrapVueMountPoint(mount_point, is_in_creation);
}

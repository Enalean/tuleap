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

import { createApp } from "vue";
import { createGettext } from "vue3-gettext";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { getDatasetItemOrThrow } from "@tuleap/dom";
import App from "./components/App.vue";
import { createInitializedStore } from "./store";
import type { ConfigurationState } from "./store/configuration";
import "../themes/main.scss";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("program-management-app");
    if (!vue_mount_point) {
        return;
    }

    const locale = getDatasetItemOrThrow(document.body, "data-user-locale");

    const gettext_plugin = await initVueGettext(
        createGettext,
        (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    const project_name = getDatasetItemOrThrow(vue_mount_point, "data-project-name");
    const project_short_name = getDatasetItemOrThrow(vue_mount_point, "data-project-short-name");
    const project_privacy = JSON.parse(
        getDatasetItemOrThrow(vue_mount_point, "data-project-privacy"),
    );
    const project_flags = JSON.parse(getDatasetItemOrThrow(vue_mount_point, "data-project-flags"));
    const program_id = parseInt(getDatasetItemOrThrow(vue_mount_point, "data-program-id"), 10);
    const program_increment_tracker_id = parseInt(
        getDatasetItemOrThrow(vue_mount_point, "data-program-increment-tracker-id"),
        10,
    );
    const program_increment_label = getDatasetItemOrThrow(
        vue_mount_point,
        "data-program-increment-label",
    );
    const program_increment_sub_label = getDatasetItemOrThrow(
        vue_mount_point,
        "data-program-increment-sub-label",
    );
    const iteration_label = getDatasetItemOrThrow(vue_mount_point, "data-iteration-label");

    const is_program_admin = Boolean(vue_mount_point.dataset.isProgramAdmin);
    const accessibility = Boolean(vue_mount_point.dataset.userWithAccessibilityMode);
    const can_create_program_increment = Boolean(vue_mount_point.dataset.canCreateProgramIncrement);
    const has_plan_permissions = Boolean(vue_mount_point.dataset.hasPlanPermissions);
    const is_iteration_tracker_defined = Boolean(vue_mount_point.dataset.isIterationTrackerDefined);
    const is_configured = Boolean(vue_mount_point.dataset.isConfigured);
    const project_icon = getDatasetItemOrThrow(vue_mount_point, "data-project-icon");

    const configuration_state: ConfigurationState = {
        public_name: project_name,
        short_name: project_short_name,
        project_icon,
        privacy: project_privacy,
        flags: project_flags,
        program_id,
        accessibility,
        user_locale: locale.replace("_", "-"),
        can_create_program_increment,
        has_plan_permissions,
        tracker_program_increment_id: program_increment_tracker_id,
        tracker_program_increment_label: program_increment_label,
        tracker_program_increment_sub_label: program_increment_sub_label,
        is_program_admin,
        is_configured,
        is_iteration_tracker_defined,
        tracker_iteration_label: iteration_label,
    };

    createApp(App)
        .use(VueDOMPurifyHTML)
        .use(createInitializedStore(configuration_state))
        .use(gettext_plugin)
        .mount(vue_mount_point);
});

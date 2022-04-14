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

import Vue from "vue";
import App from "./src/components/App.vue";
import { getPOFileFromLocale, initVueGettext } from "@tuleap/vue2-gettext-init";
import { createStore } from "./src/store";
import type { ConfigurationState } from "./src/store/configuration";
import VueDOMPurifyHTML from "vue-dompurify-html";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("program-management-app");
    if (!vue_mount_point) {
        return;
    }

    const locale = document.body.dataset.userLocale;
    if (locale === undefined) {
        throw new Error("Unable to load user locale");
    }

    Vue.config.language = locale;
    Vue.use(VueDOMPurifyHTML);

    if (!vue_mount_point.dataset.projectName) {
        throw new Error("Missing projectName dataset");
    }
    const project_name = vue_mount_point.dataset.projectName;

    if (!vue_mount_point.dataset.projectShortName) {
        throw new Error("Missing projectShortName dataset");
    }
    const project_short_name = vue_mount_point.dataset.projectShortName;

    if (!vue_mount_point.dataset.projectPrivacy) {
        throw new Error("Missing projectPrivacy dataset");
    }
    const project_privacy = JSON.parse(vue_mount_point.dataset.projectPrivacy);

    if (!vue_mount_point.dataset.projectFlags) {
        throw new Error("Missing projectFlags dataset");
    }
    const project_flags = JSON.parse(vue_mount_point.dataset.projectFlags);

    if (!vue_mount_point.dataset.programId) {
        throw new Error("Missing program_id dataset");
    }
    const program_id = parseInt(vue_mount_point.dataset.programId, 10);

    if (!vue_mount_point.dataset.programIncrementTrackerId) {
        throw new Error("Missing program_increment_tracker_id dataset");
    }
    const program_increment_tracker_id = parseInt(
        vue_mount_point.dataset.programIncrementTrackerId,
        10
    );

    if (!vue_mount_point.dataset.programIncrementLabel) {
        throw new Error("Missing program_increment_label dataset");
    }

    const program_increment_label = vue_mount_point.dataset.programIncrementLabel;

    if (!vue_mount_point.dataset.programIncrementSubLabel) {
        throw new Error("Missing program_increment_sub_label dataset");
    }

    const program_increment_sub_label = vue_mount_point.dataset.programIncrementSubLabel;

    if (!vue_mount_point.dataset.iterationLabel) {
        throw new Error("Missing iteration_label dataset");
    }
    const iteration_label = vue_mount_point.dataset.iterationLabel;

    const is_program_admin = Boolean(vue_mount_point.dataset.isProgramAdmin);
    const accessibility = Boolean(vue_mount_point.dataset.userWithAccessibilityMode);
    const can_create_program_increment = Boolean(vue_mount_point.dataset.canCreateProgramIncrement);
    const has_plan_permissions = Boolean(vue_mount_point.dataset.hasPlanPermissions);
    const is_iteration_tracker_defined = Boolean(vue_mount_point.dataset.isIterationTrackerDefined);
    const is_configured = Boolean(vue_mount_point.dataset.isConfigured);
    if (vue_mount_point.dataset.projectIcon === undefined) {
        throw new Error("Missing the project_icon dataset");
    }
    const project_icon = vue_mount_point.dataset.projectIcon;

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

    await initVueGettext(
        Vue,
        (locale: string) =>
            import(
                /* webpackChunkName: "program-management-po-" */ "./po/" +
                    getPOFileFromLocale(locale)
            )
    );

    const AppComponent = Vue.extend(App);

    new AppComponent({
        store: createStore(configuration_state),
    }).$mount(vue_mount_point);
});

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

import { createApp } from "vue";
import { createGettext } from "vue3-gettext";
import { initVueGettext, getPOFileFromLocaleWithoutExtension } from "@tuleap/vue3-gettext-init";
import App from "./components/App.vue";
import { createStore } from "./store";
import type {
    CSRFToken,
    DataForColorPicker,
    ExistingTrackersList,
    ProjectTemplate,
    ProjectWithTrackers,
    State,
    Tracker,
} from "./store/type";
import { NONE_YET } from "./store/type";
import { createInitializedRouter } from "./router/index";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("tracker-creation-app");
    if (!vue_mount_point) {
        return;
    }

    const gettext_plugin = await initVueGettext(
        createGettext,
        (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    const app = createApp(App).use(gettext_plugin);

    const csrf_token: CSRFToken | null =
        typeof vue_mount_point.dataset.csrfToken !== "undefined"
            ? JSON.parse(vue_mount_point.dataset.csrfToken)
            : null;

    if (!csrf_token) {
        throw new Error("No CSRF token");
    }

    const default_templates: Tracker[] =
        typeof vue_mount_point.dataset.defaultTemplates !== "undefined"
            ? JSON.parse(vue_mount_point.dataset.defaultTemplates)
            : [];

    const project_templates: Array<ProjectTemplate> =
        typeof vue_mount_point.dataset.projectTemplates !== "undefined"
            ? JSON.parse(vue_mount_point.dataset.projectTemplates)
            : [];

    const existing_trackers: ExistingTrackersList =
        typeof vue_mount_point.dataset.existingTrackers !== "undefined"
            ? JSON.parse(vue_mount_point.dataset.existingTrackers)
            : { names: [], shortnames: [] };

    const project_unix_name = vue_mount_point.dataset.projectUnixName;

    if (!project_unix_name) {
        throw new Error("Project name not provided, app can't be routed.");
    }

    const company_name = vue_mount_point.dataset.companyName;

    if (!company_name) {
        throw new Error("Company name not provided, app can't be routed.");
    }

    const project_id = vue_mount_point.dataset.projectId;
    if (!project_id) {
        throw new Error("Project id not provided.");
    }

    const trackers_from_other_projects: ProjectWithTrackers[] =
        typeof vue_mount_point.dataset.trackersFromOtherProjects !== "undefined"
            ? JSON.parse(vue_mount_point.dataset.trackersFromOtherProjects)
            : [];

    const tracker_colors: { colors_names: string[]; default_color: string } =
        typeof vue_mount_point.dataset.trackerColors !== "undefined"
            ? JSON.parse(vue_mount_point.dataset.trackerColors)
            : {};

    const color_picker_data: DataForColorPicker[] = tracker_colors.colors_names.map(
        (color_name: string) => ({ id: color_name, text: "" }),
    );

    const are_there_tv3 = Boolean(vue_mount_point.dataset.areThereTv3);

    const initial_state: State = {
        csrf_token,
        default_templates,
        project_templates,
        existing_trackers,
        trackers_from_other_projects,
        color_picker_data,
        default_tracker_color: tracker_colors.default_color,
        active_option: NONE_YET,
        selected_tracker_template: null,
        selected_project_tracker_template: null,
        selected_project: null,
        selected_xml_file_input: null,
        tracker_to_be_created: {
            name: "",
            shortname: "",
            color: tracker_colors.default_color,
        },
        has_form_been_submitted: false,
        is_a_xml_file_selected: false,
        is_parsing_a_xml_file: false,
        has_xml_file_error: false,
        is_in_slugify_mode: true,
        project_id: parseInt(project_id, 10),
        company_name,
        from_jira_data: {
            credentials: null,
            project: null,
            tracker: null,
            project_list: null,
            tracker_list: null,
        },
        are_there_tv3,
        project_unix_name,
    };

    app.use(createStore(initial_state))
        .use(createInitializedRouter(project_unix_name))
        .mount(vue_mount_point);
});

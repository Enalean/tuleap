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

import Vue from "vue";
import Vuex from "vuex";
import App from "./src/components/App.vue";
import { initVueGettext } from "../../../../src/www/scripts/tuleap/gettext/vue-gettext-init";
import { createStore } from "./src/store/index";
import { CreationOptions, ProjectTemplate } from "./src/store/type";
import { createRouter } from "./src/router";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("tracker-creation-app");
    if (!vue_mount_point) {
        return;
    }

    await initVueGettext(Vue, (locale: string) =>
        import(/* webpackChunkName: "tracker-creation-po" */ `./po/${locale}.po`)
    );

    const AppComponent = Vue.extend(App);
    Vue.use(Vuex);

    const project_templates: Array<ProjectTemplate> =
        typeof vue_mount_point.dataset.projectTemplates !== "undefined"
            ? JSON.parse(vue_mount_point.dataset.projectTemplates)
            : [];

    const project_unix_name = vue_mount_point.dataset.projectUnixName;

    if (!project_unix_name) {
        throw new Error("Project name not provided, app can't be routed.");
    }

    const initial_state = {
        project_templates,
        active_option: CreationOptions.NONE_YET,
        selected_tracker_template: null
    };

    new AppComponent({
        store: createStore(initial_state),
        router: createRouter(project_unix_name)
    }).$mount(vue_mount_point);
});

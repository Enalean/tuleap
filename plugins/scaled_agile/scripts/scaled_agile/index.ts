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
import {
    initVueGettext,
    getPOFileFromLocale,
} from "@tuleap/core/scripts/tuleap/gettext/vue-gettext-init";
import { build } from "./src/configuration";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("scaled-agile");
    if (!vue_mount_point) {
        return;
    }

    if (!vue_mount_point.dataset.projectName) {
        throw new Error("Missing projectName dataset");
    }
    const project_name = vue_mount_point.dataset.projectName;

    if (!vue_mount_point.dataset.projectShortName) {
        throw new Error("Missing projectShortName dataset");
    }
    const project_short_name = vue_mount_point.dataset.projectShortName;

    build(project_name, project_short_name);

    await initVueGettext(
        Vue,
        (locale: string) =>
            import(/* webpackChunkName: "scaled-agile-po-" */ "./po/" + getPOFileFromLocale(locale))
    );

    const AppComponent = Vue.extend(App);

    new AppComponent({}).$mount(vue_mount_point);
});

/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
import { initVueGettextFromPoGettextPlugin, getPOFileFromLocale } from "@tuleap/vue2-gettext-init";
import SvnPermissions from "./SVNPermissions.vue";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("svn-permission-per-group");

    if (!vue_mount_point) {
        return;
    }

    const project_id = vue_mount_point.dataset.projectId;
    if (project_id === undefined) {
        throw new Error("Could not read data-project-id from mount point");
    }

    await initVueGettextFromPoGettextPlugin(Vue, (locale) =>
        import(`../po/${getPOFileFromLocale(locale)}`),
    );

    const RootComponent = Vue.extend(SvnPermissions);
    new RootComponent({
        propsData: { projectId: project_id },
    }).$mount(vue_mount_point);
});

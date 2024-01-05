/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";

import NewsPermissions from "./BaseNewsPermissions.vue";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("news-permission-per-group");
    if (!vue_mount_point) {
        return;
    }

    const gettext = await initVueGettext(createGettext, (locale: string) => {
        return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
    });
    const app = createApp(NewsPermissions, {
        selected_project_id: vue_mount_point.dataset.selectedProjectId,
        selected_ugroup_id: vue_mount_point.dataset.selectedUgroupId,
        selected_ugroup_name: vue_mount_point.dataset.selectedUgroupName,
    });
    app.use(gettext);
    app.mount(vue_mount_point);
});

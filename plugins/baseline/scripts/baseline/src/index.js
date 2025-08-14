/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

import { createApp } from "vue";
import { getPOFileFromLocale, initVueGettext } from "@tuleap/vue3-gettext-init";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { createGettext } from "vue3-gettext";
import { createInitializedRouter } from "./router/index";
import App from "./components/App.vue";
import store from "./store/index";
import DateUtils from "./support/date-utils";

document.addEventListener("DOMContentLoaded", async () => {
    DateUtils.setOptions({
        user_locale: document.body.dataset.userLocale,
        user_timezone: document.body.dataset.userTimezone,
        format: document.body.dataset.dateTimeFormat,
    });

    const vue_mount_point = document.getElementById("baseline-container");
    if (!vue_mount_point) {
        return;
    }

    const project_id = Number(vue_mount_point.dataset.projectId);
    const project_public_name = vue_mount_point.dataset.projectPublicName;
    const project_short_name = vue_mount_point.dataset.projectShortName;
    const project_icon = vue_mount_point.dataset.projectIcon;
    const project_url = vue_mount_point.dataset.projectUrl;
    const privacy = JSON.parse(vue_mount_point.dataset.privacy);
    const project_flags = JSON.parse(vue_mount_point.dataset.projectFlags);
    const is_admin = Boolean(vue_mount_point.dataset.isAdmin);
    const admin_url = vue_mount_point.dataset.adminUrl;

    createApp(App, {
        project_id,
        project_public_name,
        project_icon,
        project_url,
        privacy,
        project_flags,
        is_admin,
        admin_url,
    })
        .provide("is_admin", is_admin)
        .use(VueDOMPurifyHTML)
        .use(
            await initVueGettext(
                createGettext,
                (locale) => import(`../po/${getPOFileFromLocale(locale)}`),
            ),
        )
        .use(
            createInitializedRouter(
                store,
                `/plugins/baseline/${encodeURIComponent(project_short_name)}`,
            ),
        )
        .use(store)
        .mount(vue_mount_point);
});

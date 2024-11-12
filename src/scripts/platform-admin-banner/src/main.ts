/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
import { getPOFileFromLocale, initVueGettext } from "@tuleap/vue3-gettext-init";
import App from "./components/App.vue";
import { LocationHelper } from "./helpers/LocationHelper";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("platform-banner-admin");
    if (!vue_mount_point) {
        return;
    }

    const gettext_plugin = await initVueGettext(
        createGettext,
        (locale: string) => import(`../po/${getPOFileFromLocale(locale)}`),
    );
    const location_helper = LocationHelper(window.location);
    createApp(App, {
        message: vue_mount_point.dataset.bannerMessage ?? "",
        importance: vue_mount_point.dataset.bannerImportance ?? "standard",
        expiration_date: vue_mount_point.dataset.bannerExpirationDate ?? "",
        location_helper,
    })
        .use(gettext_plugin)
        .mount(vue_mount_point);
});

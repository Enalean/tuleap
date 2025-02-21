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

import { createApp } from "vue";
import { createGettext } from "vue3-gettext";
import App from "./components/App.vue";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createInitializedStore } from "./store";
import { getAttributeOrThrow } from "@tuleap/dom";
import "../themes/main.scss";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("planned-iterations-app");
    if (!vue_mount_point) {
        return;
    }

    const user_locale = getAttributeOrThrow(document.body, "data-user-locale");

    const gettext_plugin = await initVueGettext(
        createGettext,
        (locale: string) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    createApp(App)
        .use(
            createInitializedStore({
                program: JSON.parse(getAttributeOrThrow(vue_mount_point, "data-program")),
                program_privacy: JSON.parse(
                    getAttributeOrThrow(vue_mount_point, "data-program-privacy"),
                ),
                program_flags: JSON.parse(
                    getAttributeOrThrow(vue_mount_point, "data-program-flags"),
                ),
                is_program_admin: Boolean(vue_mount_point.getAttribute("data-is-user-admin")),
                program_increment: JSON.parse(
                    getAttributeOrThrow(vue_mount_point, "data-program-increment"),
                ),
                iterations_labels: JSON.parse(
                    getAttributeOrThrow(vue_mount_point, "data-iterations-labels"),
                ),
                user_locale: user_locale.replace("_", "-"),
                iteration_tracker_id: Number.parseInt(
                    getAttributeOrThrow(vue_mount_point, "data-iteration-tracker-id"),
                    10,
                ),
                is_accessibility_mode_enabled: Boolean(
                    vue_mount_point.getAttribute("data-is-accessibility-mode-enabled"),
                ),
            }),
        )
        .use(gettext_plugin)
        .mount(vue_mount_point);
});

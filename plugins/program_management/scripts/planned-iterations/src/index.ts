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

import Vue from "vue";
import App from "./components/App.vue";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue2-gettext-init";
import { createStore } from "./store";
import { getDatasetItemOrThrow } from "@tuleap/dom";
import "../themes/main.scss";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("planned-iterations-app");
    if (!vue_mount_point) {
        return;
    }

    const user_locale = getDatasetItemOrThrow(document.body, "userLocale");
    Vue.config.language = user_locale;

    await initVueGettext(
        Vue,
        (locale: string) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    const AppComponent = Vue.extend(App);

    new AppComponent({
        store: createStore({
            program: JSON.parse(getDatasetItemOrThrow(vue_mount_point, "program")),
            program_privacy: JSON.parse(getDatasetItemOrThrow(vue_mount_point, "programPrivacy")),
            program_flags: JSON.parse(getDatasetItemOrThrow(vue_mount_point, "programFlags")),
            is_program_admin: Boolean(vue_mount_point.dataset.isUserAdmin),
            program_increment: JSON.parse(
                getDatasetItemOrThrow(vue_mount_point, "programIncrement"),
            ),
            iterations_labels: JSON.parse(
                getDatasetItemOrThrow(vue_mount_point, "iterationsLabels"),
            ),
            user_locale: user_locale.replace("_", "-"),
            iteration_tracker_id: parseInt(
                getDatasetItemOrThrow(vue_mount_point, "iterationTrackerId"),
                10,
            ),
            is_accessibility_mode_enabled: Boolean(
                vue_mount_point.dataset.isAccessibilityModeEnabled,
            ),
        }),
    }).$mount(vue_mount_point);
});

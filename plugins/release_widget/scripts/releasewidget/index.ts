/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import Vue from "vue";
import GetTextPlugin from "vue-gettext";
import VueDOMPurifyHTML from "vue-dompurify-html";
// @ts-ignore
import french_translations from "./po/fr.po";
import App from "./src/components/App.vue";
import { createStore } from "./src/store/index";
import { setUserLocale } from "./src/helpers/user-locale-helper";

document.addEventListener("DOMContentLoaded", () => {
    Vue.use(GetTextPlugin, {
        translations: {
            fr: french_translations.messages
        },
        silent: true
    });

    Vue.use(VueDOMPurifyHTML);

    const locale = document.body.dataset.userLocale;
    if (locale !== undefined) {
        Vue.config.language = locale;
        setUserLocale(locale.replace("_", "-"));
    }

    const vue_mount_point = document.getElementById("release-widget");

    if (!vue_mount_point) {
        return;
    }

    const project_id_dataset = vue_mount_point.dataset.projectId;

    if (!project_id_dataset) {
        throw new Error("Project Id is missing.");
    }

    const project_id = Number.parseInt(project_id_dataset, 10);

    const AppComponent = Vue.extend(App);
    const store = createStore();

    new AppComponent({
        store,
        propsData: {
            projectId: project_id
        }
    }).$mount(vue_mount_point);
});

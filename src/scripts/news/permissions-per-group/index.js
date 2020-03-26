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

import Vue from "vue";
import GettextPlugin from "vue-gettext";
import french_translations from "./po/fr.po";

import NewsPermissions from "./BaseNewsPermissions.vue";

document.addEventListener("DOMContentLoaded", () => {
    const vue_mount_point = document.getElementById("news-permission-per-group");

    if (!vue_mount_point) {
        return;
    }

    Vue.use(GettextPlugin, {
        translations: {
            fr: french_translations.messages,
        },
        silent: true,
    });

    const rootComponent = Vue.extend(NewsPermissions);
    const locale = document.body.dataset.userLocale;
    Vue.config.language = locale;

    new rootComponent({
        propsData: { ...vue_mount_point.dataset },
    }).$mount(vue_mount_point);
});

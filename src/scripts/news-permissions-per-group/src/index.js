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
import { initVueGettextFromPoGettextPlugin, getPOFileFromLocale } from "@tuleap/vue2-gettext-init";

import NewsPermissions from "./BaseNewsPermissions.vue";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("news-permission-per-group");

    if (!vue_mount_point) {
        return;
    }

    await initVueGettextFromPoGettextPlugin(Vue, (locale) =>
        import(
            /* webpackChunkName: "permissions-per-group-news-po-" */ "../po/" +
                getPOFileFromLocale(locale)
        )
    );

    const rootComponent = Vue.extend(NewsPermissions);
    new rootComponent({
        propsData: { ...vue_mount_point.dataset },
    }).$mount(vue_mount_point);
});

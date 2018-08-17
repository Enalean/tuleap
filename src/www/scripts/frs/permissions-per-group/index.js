/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
import NewsPermissions from "./BaseFRSPackagePermissions.vue";
import { gettext_provider } from "./gettext-provider.js";

document.addEventListener("DOMContentLoaded", () => {
    const vue_mount_point = document.getElementById("frs-packages-permissions-per-group");

    if (!vue_mount_point) {
        return;
    }

    const rootComponent = Vue.extend(NewsPermissions);
    const locale = document.body.dataset.userLocale;

    gettext_provider.setLocale(locale);

    new rootComponent({
        propsData: { ...vue_mount_point.dataset }
    }).$mount(vue_mount_point);
});

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
import VueDOMPurifyHTML from "vue-dompurify-html";
import { createStore } from "./src/store";
import App from "./src/components/App.vue";
import { initVueGettext } from "../../../../src/www/scripts/tuleap/gettext/vue-gettext-init";
import { ColumnDefinition, RootState } from "./src/type";
import Vuex from "vuex";
import { UserState } from "./src/store/user/type";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("taskboard");
    if (!vue_mount_point) {
        return;
    }

    const user_is_admin = Boolean(vue_mount_point.dataset.userIsAdmin);
    const user_id_string = document.body.dataset.userId || "0";
    const user_id = Number.parseInt(user_id_string, 10);
    const admin_url = vue_mount_point.dataset.adminUrl || "";
    const milestone_title = vue_mount_point.dataset.milestoneTitle || "";
    const columns: Array<ColumnDefinition> =
        typeof vue_mount_point.dataset.columns !== "undefined"
            ? JSON.parse(vue_mount_point.dataset.columns)
            : [];
    const has_content = Boolean(vue_mount_point.dataset.hasContent);
    const milestone_id = Number.parseInt(vue_mount_point.dataset.milestoneId || "0", 10);
    const user_has_accessibility_mode = Boolean(document.body.dataset.userHasAccessibilityMode);

    await initVueGettext(Vue, (locale: string) =>
        import(/* webpackChunkName: "taskboard-po-" */ `./po/${locale}.po`)
    );
    Vue.use(Vuex);
    Vue.use(VueDOMPurifyHTML);

    const AppComponent = Vue.extend(App);

    const initial_root_state: RootState = {
        admin_url,
        columns,
        has_content,
        milestone_id,
        milestone_title
    };

    const initial_user_state: UserState = {
        user_is_admin,
        user_id,
        user_has_accessibility_mode
    };

    new AppComponent({
        store: createStore(initial_root_state, initial_user_state)
    }).$mount(vue_mount_point);
});

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
import { ColumnDefinition, State, Swimlane } from "./src/type";
import Vuex from "vuex";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("taskboard");
    if (!vue_mount_point) {
        return;
    }

    const user_is_admin = Boolean(vue_mount_point.dataset.userIsAdmin);
    const user_id_string = document.body.dataset.userId || "0";
    const user_id = Number.parseInt(user_id_string, 10);
    const admin_url = vue_mount_point.dataset.adminUrl || "";
    const columns: Array<ColumnDefinition> =
        typeof vue_mount_point.dataset.columns !== "undefined"
            ? JSON.parse(vue_mount_point.dataset.columns)
            : [];
    const has_content = Boolean(vue_mount_point.dataset.hasContent);
    const swimlanes: Array<Swimlane> = [];
    const milestone_id = Number.parseInt(vue_mount_point.dataset.milestoneId || "0", 10);

    await initVueGettext(Vue, (locale: string) =>
        import(/* webpackChunkName: "taskboard-po-" */ `./po/${locale}.po`)
    );
    Vue.use(Vuex);
    Vue.use(VueDOMPurifyHTML);

    const AppComponent = Vue.extend(App);

    const initial_state: State = {
        user_is_admin,
        admin_url,
        user_id,
        columns,
        swimlanes,
        has_content,
        milestone_id
    };
    new AppComponent({
        store: createStore(initial_state)
    }).$mount(vue_mount_point);
});

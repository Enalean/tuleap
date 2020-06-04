/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import Vuex from "vuex";
import {
    getPOFileFromLocale,
    initVueGettext,
} from "../../../../src/scripts/tuleap/gettext/vue-gettext-init";
import App from "./src/components/App.vue";
import { createStore } from "./src/store";
import { RootState } from "./src/store/type";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("test-plan");
    if (!vue_mount_point) {
        return;
    }

    const project_id = Number.parseInt(vue_mount_point.dataset.projectId || "0", 10);
    const milestone_id = Number.parseInt(vue_mount_point.dataset.milestoneId || "0", 10);

    await initVueGettext(Vue, (locale: string) =>
        import(/* webpackChunkName: "testplan-po-" */ "./po/" + getPOFileFromLocale(locale))
    );
    Vue.use(Vuex);

    const AppComponent = Vue.extend(App);

    // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
    const initial_state = ({ project_id, milestone_id } as unknown) as RootState;

    new AppComponent({
        store: createStore(initial_state),
    }).$mount(vue_mount_point);
});

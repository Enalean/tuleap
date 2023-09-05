/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
import Vuex from "vuex";
import store_options from "./store/index.js";
import { getPOFileFromLocale, initVueGettextFromPoGettextPlugin } from "@tuleap/vue2-gettext-init";
import VueDOMPurifyHTML from "vue-dompurify-html";
import BaseTrackerWorkflowTransitions from "./components/BaseTrackerWorkflowTransitions.vue";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("tracker-workflow-transitions");

    if (!vue_mount_point) {
        return;
    }

    const service_name_list = vue_mount_point.dataset.usedServicesNames;
    if (!service_name_list) {
        return;
    }
    const used_services_names = JSON.parse(service_name_list);

    const is_split_feature_flag_enabled = vue_mount_point.dataset.isSplitFeatureFlagEnabled === "1";
    Vue.use(Vuex);
    await initVueGettextFromPoGettextPlugin(Vue, (locale) =>
        import("../po/" + getPOFileFromLocale(locale)),
    );
    Vue.use(VueDOMPurifyHTML);

    const RootComponent = Vue.extend(BaseTrackerWorkflowTransitions);
    const trackerId = Number.parseInt(vue_mount_point.dataset.trackerId, 10);
    const store = new Vuex.Store(store_options);

    new RootComponent({
        store,
        propsData: { trackerId, used_services_names, is_split_feature_flag_enabled },
    }).$mount(vue_mount_point);
});

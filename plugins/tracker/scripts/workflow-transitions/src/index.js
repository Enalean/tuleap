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

import { createApp } from "vue";
import { createGettext } from "vue3-gettext";
import store_options from "./store/index.js";
import { getPOFileFromLocale, initVueGettext } from "@tuleap/vue3-gettext-init";
import VueDOMPurifyHTML from "vue-dompurify-html";
import BaseTrackerWorkflowTransitions from "./components/BaseTrackerWorkflowTransitions.vue";
import "./tracker-email-copy-paste-bp.js";
import { createStore } from "vuex";

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

    const trackerId = Number.parseInt(vue_mount_point.dataset.trackerId, 10);
    const store = createStore(store_options);

    createApp(BaseTrackerWorkflowTransitions, {
        trackerId,
        used_services_names,
    })
        .use(VueDOMPurifyHTML)
        .use(
            await initVueGettext(
                createGettext,
                (locale) => import(`../po/${getPOFileFromLocale(locale)}`),
            ),
        )
        .use(store)
        .mount(vue_mount_point);
});

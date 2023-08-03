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
import Vuex from "vuex";
import MoveModal from "./components/MoveModal.vue";
import { setFromTracker } from "./from-tracker-presenter.ts";
import { getPOFileFromLocale, initVueGettextFromPoGettextPlugin } from "@tuleap/vue2-gettext-init";
import { createStore } from "./store/index.ts";

export async function init(vue_mount_point) {
    Vue.config.language = document.body.dataset.userLocale ?? "en_US";
    await initVueGettextFromPoGettextPlugin(Vue, (locale) =>
        import("../po/" + getPOFileFromLocale(locale))
    );

    const RootComponent = Vue.extend(MoveModal);

    const { trackerId, trackerName, trackerColor, artifactId, projectId } = vue_mount_point.dataset;

    setFromTracker(
        Number.parseInt(trackerId, 10),
        trackerName,
        trackerColor,
        artifactId,
        projectId
    );

    Vue.use(Vuex);

    new RootComponent({
        store: createStore(),
    }).$mount(vue_mount_point);
}

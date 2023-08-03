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

import type { ColorName } from "@tuleap/core-constants";
import { getDatasetItemOrThrow } from "@tuleap/dom";
import Vue from "vue";
import Vuex from "vuex";
import MoveModal from "./components/MoveModal.vue";
import { setFromTracker } from "./from-tracker-presenter";
import { getPOFileFromLocale, initVueGettextFromPoGettextPlugin } from "@tuleap/vue2-gettext-init";
import { createStore } from "./store";

const getTlpColorName = (color_name: string): color_name is ColorName => true;

export async function init(vue_mount_point: HTMLElement): Promise<void> {
    Vue.config.language = document.body.dataset.userLocale ?? "en_US";
    await initVueGettextFromPoGettextPlugin(
        Vue,
        (locale) => import("../po/" + getPOFileFromLocale(locale))
    );

    const RootComponent = Vue.extend(MoveModal);

    const color_name = getDatasetItemOrThrow(vue_mount_point, "trackerColor");
    if (!getTlpColorName(color_name)) {
        return;
    }

    setFromTracker(
        Number.parseInt(getDatasetItemOrThrow(vue_mount_point, "trackerId"), 10),
        getDatasetItemOrThrow(vue_mount_point, "trackerName"),
        color_name,
        Number.parseInt(getDatasetItemOrThrow(vue_mount_point, "artifactId"), 10),
        Number.parseInt(getDatasetItemOrThrow(vue_mount_point, "projectId"), 10)
    );

    Vue.use(Vuex);

    new RootComponent({
        store: createStore(),
    }).$mount(vue_mount_point);
}

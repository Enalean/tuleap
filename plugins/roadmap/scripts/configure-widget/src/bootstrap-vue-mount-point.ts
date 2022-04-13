/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { getPOFileFromLocale, initVueGettext } from "@tuleap/vue2-gettext-init";
import Vue from "vue";
import App from "./components/App.vue";

export async function bootstrapVueMountPoint(
    mount_point: HTMLElement,
    is_in_creation: boolean
): Promise<void> {
    await initVueGettext(
        Vue,
        (locale: string) =>
            import(
                /* webpackChunkName: "configure-roadmap-widget-po-" */ "../po/" +
                    getPOFileFromLocale(locale)
            )
    );

    const AppComponent = Vue.extend(App);

    new AppComponent({
        propsData: {
            widget_id: Number.parseInt(mount_point.dataset.widgetId || "0", 10),
            title: String(mount_point.dataset.title),
            trackers:
                typeof mount_point.dataset.trackers !== "undefined"
                    ? JSON.parse(mount_point.dataset.trackers) || []
                    : [],
            selected_tracker_ids:
                typeof mount_point.dataset.selectedTrackerIds !== "undefined"
                    ? JSON.parse(mount_point.dataset.selectedTrackerIds) || []
                    : [],
            selected_lvl1_iteration_tracker_id:
                Number.parseInt(mount_point.dataset.selectedLvl1IterationTrackerId || "0", 10) ||
                "",
            selected_lvl2_iteration_tracker_id:
                Number.parseInt(mount_point.dataset.selectedLvl2IterationTrackerId || "0", 10) ||
                "",
            selected_default_timescale: String(mount_point.dataset.defaultTimescale || "month"),
            is_in_creation,
        },
    }).$mount(mount_point);
}

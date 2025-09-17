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

import { createApp } from "vue";
import { createGettext } from "vue3-gettext";
import VueDOMPurifyHTML from "vue-dompurify-html";
import App from "./components/App.vue";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { parseNatureLabels } from "./helpers/nature-labels-from-mountpoint";
import { createInitializedStore } from "./store";
import type { RootState } from "./store/type";
import { toBCP47 } from "./helpers/locale-for-intl";
import type { TimeScale } from "./type";
import { Settings } from "luxon";
import "./style/widget-roadmap.scss";
import type { VueGettextProvider } from "./helpers/vue-gettext-provider";
import { getAttributeOrThrow } from "@tuleap/dom";
import { DASHBOARD_ID } from "./injection-symbols";

document.addEventListener("DOMContentLoaded", async () => {
    const timezone = document.body.dataset.userTimezone;
    if (timezone) {
        Settings.defaultZone = timezone;
    }

    const all_vue_mount_points = document.querySelectorAll(".roadmap");
    if (all_vue_mount_points.length === 0) {
        return;
    }

    for (const vue_mount_point of all_vue_mount_points) {
        if (!(vue_mount_point instanceof HTMLElement)) {
            continue;
        }

        const dashboard_id = Number.parseInt(
            getAttributeOrThrow(vue_mount_point, "data-dashboard-id"),
            10,
        );
        const roadmap_id = Number.parseInt(
            getAttributeOrThrow(vue_mount_point, "data-roadmap-id"),
            10,
        );

        const should_load_lvl1_iterations = getAttributeOrThrow(
            vue_mount_point,
            "data-should-load-lvl1-iterations",
        );
        const should_load_lvl2_iterations = getAttributeOrThrow(
            vue_mount_point,
            "data-should-load-lvl2-iterations",
        );

        const gettext_plugin = await initVueGettext(
            /** @ts-expect-error vue3-gettext-init is tested with Vue 3.4, but here we use Vue 3.5 */
            createGettext,
            (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
        );

        const gettext_provider: VueGettextProvider = {
            $gettext: gettext_plugin.$gettext,
            $gettextInterpolate: gettext_plugin.interpolate,
        };
        const visible_natures = await parseNatureLabels(vue_mount_point, gettext_provider);

        // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
        const initial_root_state: RootState = {
            gettext_provider,
            locale_bcp47: toBCP47(document.body.dataset.userLocale || "en_US"),
            should_load_lvl1_iterations: Boolean(should_load_lvl1_iterations),
            should_load_lvl2_iterations: Boolean(should_load_lvl2_iterations),
        } as RootState;

        const default_timescale: TimeScale = ((
            default_timescale: string | undefined,
        ): TimeScale => {
            if (
                default_timescale === "week" ||
                default_timescale === "month" ||
                default_timescale === "quarter"
            ) {
                return default_timescale;
            }

            return "month";
        })(vue_mount_point.dataset.defaultTimescale);

        createApp(App, {
            roadmap_id,
            visible_natures,
        })
            .use(VueDOMPurifyHTML)
            /** @ts-expect-error vue3-gettext-init is tested with Vue 3.4, but here we use Vue 3.5 */
            .use(gettext_plugin)
            .use(createInitializedStore(initial_root_state, default_timescale))
            .provide(DASHBOARD_ID, dashboard_id)
            .mount(vue_mount_point);
    }
});

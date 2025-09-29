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

import { createPinia } from "pinia";
import { createApp } from "vue";
import { useProjectTimetrackingWidgetStore } from "./store";
import ProjectTimetracking from "./components/ProjectTimetracking.vue";
import { getPOFileFromLocale, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";

document.addEventListener("DOMContentLoaded", async () => {
    const widgets: NodeListOf<HTMLElement> = document.querySelectorAll(
        ".project-timetracking-widget",
    );
    if (widgets.length === 0) {
        return;
    }

    if (!document.body.dataset.userId) {
        throw new Error("dataset userId not found");
    }

    const user_id = parseInt(document.body.dataset.userId, 10);

    for (const widget_element of widgets) {
        if (!widget_element.dataset.reportId) {
            throw new Error("dataset reportId not found");
        }

        const report_id = Number.parseInt(widget_element.dataset.reportId, 10);
        const are_void_trackers_hidden = widget_element.dataset.displayPreference === "true";

        const app = createApp(ProjectTimetracking, {
            report_id: report_id,
            user_id: user_id,
            are_void_trackers_hidden: are_void_trackers_hidden,
        });

        const pinia = createPinia();
        useProjectTimetrackingWidgetStore(report_id)(pinia);

        app.use(pinia);
        app.use(
            /** @ts-expect-error vue3-gettext-init is tested with Vue 3.4, but here we use Vue 3.5 */
            await initVueGettext(
                /** @ts-expect-error vue3-gettext-init is tested with Vue 3.4, but here we use Vue 3.5 */
                createGettext,
                (locale) => import(`../po/${getPOFileFromLocale(locale)}`),
            ),
        );
        app.mount(widget_element);
    }
});

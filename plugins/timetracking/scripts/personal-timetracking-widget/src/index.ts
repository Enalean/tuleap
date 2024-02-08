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

import { createApp } from "vue";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";
import { createPinia } from "pinia";
import TimetrackingWidget from "./components/TimetrackingWidget.vue";

document.addEventListener("DOMContentLoaded", async () => {
    const mount_point = document.getElementById("personal-timetracking-widget");
    if (!(mount_point instanceof HTMLElement)) {
        return;
    }

    if (!document.body.dataset.userId) {
        return;
    }

    createApp(TimetrackingWidget, {
        userId: parseInt(document.body.dataset.userId, 10),
        userLocale: document.body.dataset.userLocale ?? "en-US",
    })
        .use(
            await initVueGettext(createGettext, (locale: string) => {
                return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
            }),
        )
        .use(createPinia())
        .mount(mount_point);
});

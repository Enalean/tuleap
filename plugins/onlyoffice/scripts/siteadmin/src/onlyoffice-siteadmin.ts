/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";
import { createApp } from "vue";
import { CONFIG } from "./injection-keys";
import App from "./components/App.vue";

document.addEventListener("DOMContentLoaded", async () => {
    const gettext_provider = await initVueGettext(
        createGettext,
        (locale: string) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    const mount_point = document.getElementById("onlyoffice-admin-servers-app");
    if (mount_point && mount_point.dataset.config) {
        const config = JSON.parse(mount_point.dataset.config);

        const app = createApp(App, {
            location: window.location,
            history: window.history,
        });
        app.use(gettext_provider);
        app.provide(CONFIG, config);
        app.mount(mount_point);
    }
});

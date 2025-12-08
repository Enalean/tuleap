/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
import { selectOrThrow, getAttributeOrThrow } from "@tuleap/dom";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import CommitChangesApp from "./components/App.vue";
import { buildBaseUrl, buildChangesTabUrl } from "./router/base-url-builders";
import { createChangesRouter } from "./router/router";
import { CHANGES_APP_BASE_URL_KEY, CURRENT_USER_ID_KEY } from "./constants";

document.addEventListener("DOMContentLoaded", async () => {
    const mount_point = selectOrThrow(document, ".vue-changes-mount-point");
    const project_id = getAttributeOrThrow(mount_point, "data-project-id");
    const current_repository_id = getAttributeOrThrow(mount_point, "data-repository-id");
    const user_id = Number.parseInt(getAttributeOrThrow(document.body, "data-user-id"), 10);
    const base_url = buildBaseUrl(window.location, current_repository_id, project_id);

    createApp(CommitChangesApp)
        .provide(CHANGES_APP_BASE_URL_KEY, base_url)
        .provide(CURRENT_USER_ID_KEY, user_id)
        .use(createChangesRouter(buildChangesTabUrl(base_url)))
        .use(
            await initVueGettext(
                createGettext,
                (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
            ),
        )
        .mount(mount_point);
});

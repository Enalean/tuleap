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
import VueDOMPurifyHTML from "vue-dompurify-html";
import { createGettext } from "vue3-gettext";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { selectOrThrow, getAttributeOrThrow } from "@tuleap/dom";
import { buildBaseUrl, buildCommitsTabUrl } from "./router/base-url-builders";
import CommitListApp from "./components/App.vue";
import { createCommitsRouter } from "./router/router";
import { COMMITS_APP_BASE_URL_KEY } from "./constants";

document.addEventListener("DOMContentLoaded", async () => {
    const mount_point = selectOrThrow(document, ".vue-commits-mount-point");
    const project_id = getAttributeOrThrow(mount_point, "data-project-id");
    const current_repository_id = getAttributeOrThrow(mount_point, "data-repository-id");
    const base_url = buildBaseUrl(window.location, current_repository_id, project_id);

    createApp(CommitListApp)
        .provide(COMMITS_APP_BASE_URL_KEY, base_url)
        .use(createCommitsRouter(buildCommitsTabUrl(base_url)))
        .use(VueDOMPurifyHTML)
        .use(
            await initVueGettext(
                createGettext,
                (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
            ),
        )
        .mount(mount_point);
});

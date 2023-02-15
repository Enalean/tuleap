/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { createApp, readonly } from "vue";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";
import { getDatasetItemOrThrow } from "@tuleap/dom";

import { OVERVIEW_APP_BASE_URL_KEY } from "./constants";
import OverviewApp from "./components/OverviewApp.vue";
import { createOverviewRouter } from "./router/router";

export async function init(mount_point: HTMLElement): Promise<void> {
    const repository_id = getDatasetItemOrThrow(mount_point, "repositoryId");
    const project_id = getDatasetItemOrThrow(mount_point, "projectId");

    const base_url = new URL("/plugins/git/", window.location.origin);
    base_url.searchParams.set("action", "pull-requests");
    base_url.searchParams.set("repo_id", encodeURIComponent(repository_id));
    base_url.searchParams.set("group_id", encodeURIComponent(project_id));

    createApp(OverviewApp)
        .provide(OVERVIEW_APP_BASE_URL_KEY, readonly(base_url))
        .use(createOverviewRouter(base_url))
        .use(
            await initVueGettext(createGettext, (locale: string) => {
                return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
            })
        )
        .mount(mount_point);
}

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
import { createOverviewRouter } from "./router/router";
import { buildBaseUrl } from "./router/base-url-builders";
import VueDOMPurifyHTML from "vue-dompurify-html";
import OverviewApp from "./components/OverviewApp.vue";
import {
    CURRENT_USER_AVATAR_URL,
    CURRENT_USER_ID,
    ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY,
    OVERVIEW_APP_BASE_URL_KEY,
    USER_DATE_TIME_FORMAT_KEY,
    USER_LOCALE_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
    PROJECT_ID,
    IS_COMMENT_EDITION_ENABLED,
} from "./constants";

export async function init(mount_point: HTMLElement): Promise<void> {
    const project_id = getDatasetItemOrThrow(mount_point, "projectId");
    const base_url = buildBaseUrl(
        window.location,
        getDatasetItemOrThrow(mount_point, "repositoryId"),
        project_id,
    );

    createApp(OverviewApp)
        .provide(OVERVIEW_APP_BASE_URL_KEY, readonly(base_url))
        .provide(USER_LOCALE_KEY, getDatasetItemOrThrow(document.body, "userLocale"))
        .provide(USER_DATE_TIME_FORMAT_KEY, getDatasetItemOrThrow(document.body, "dateTimeFormat"))
        .provide(
            CURRENT_USER_ID,
            Number.parseInt(getDatasetItemOrThrow(document.body, "userId"), 10),
        )
        .provide(PROJECT_ID, project_id)
        .provide(CURRENT_USER_AVATAR_URL, getDatasetItemOrThrow(mount_point, "userAvatarUrl"))
        .provide(
            USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
            getDatasetItemOrThrow(mount_point, "relativeDateDisplay"),
        )
        .provide(
            ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY,
            Boolean(getDatasetItemOrThrow(mount_point, "areMergeCommitsAllowedInRepository")),
        )
        .provide(
            IS_COMMENT_EDITION_ENABLED,
            Boolean(getDatasetItemOrThrow(mount_point, "isCommentEditionEnabled")),
        )
        .use(createOverviewRouter(base_url))
        .use(
            await initVueGettext(createGettext, (locale: string) => {
                return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
            }),
        )
        .use(VueDOMPurifyHTML)
        .mount(mount_point);
}

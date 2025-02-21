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

import { createApp, ref } from "vue";
import { createGettext } from "vue3-gettext";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { getLocaleOrThrow, getTimezoneOrThrow } from "@tuleap/date-helper";
import { getAttributeOrThrow } from "@tuleap/dom";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { buildBaseUrl } from "./urls/base-url-builders";
import HomePage from "./components/HomePage.vue";
import {
    BASE_URL,
    PROJECT_ID,
    PULL_REQUEST_SORT_ORDER,
    REPOSITORY_ID,
    SHOW_CLOSED_PULL_REQUESTS,
    USER_LOCALE_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
    SORT_DESCENDANT,
    SHOW_PULL_REQUESTS_RELATED_TO_ME,
    CURRENT_USER_ID,
    USER_TIMEZONE_KEY,
} from "./injection-symbols";

export const init = async (mount_point: HTMLElement): Promise<void> => {
    const repository_id = Number.parseInt(
        getAttributeOrThrow(mount_point, "data-repository-id"),
        10,
    );
    const project_id = Number.parseInt(getAttributeOrThrow(mount_point, "data-project-id"), 10);
    const user_id = Number.parseInt(getAttributeOrThrow(document.body, "data-user-id"), 10);
    const base_url = buildBaseUrl(window.location, repository_id, project_id);

    createApp(HomePage)
        .provide(REPOSITORY_ID, repository_id)
        .provide(PROJECT_ID, project_id)
        .provide(CURRENT_USER_ID, user_id)
        .provide(BASE_URL, base_url)
        .provide(USER_LOCALE_KEY, getLocaleOrThrow(document))
        .provide(USER_TIMEZONE_KEY, getTimezoneOrThrow(document))
        .provide(
            USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
            getAttributeOrThrow(mount_point, "data-relative-date-display"),
        )
        .provide(SHOW_CLOSED_PULL_REQUESTS, ref(false))
        .provide(SHOW_PULL_REQUESTS_RELATED_TO_ME, ref(false))
        .provide(PULL_REQUEST_SORT_ORDER, ref(SORT_DESCENDANT))
        .use(VueDOMPurifyHTML)
        .use(
            await initVueGettext(createGettext, (locale: string) => {
                return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
            }),
        )
        .mount(mount_point);
};

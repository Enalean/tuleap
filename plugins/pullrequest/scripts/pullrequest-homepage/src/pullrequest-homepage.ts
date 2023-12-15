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

import { createApp } from "vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { getDatasetItemOrThrow } from "@tuleap/dom";
import { buildBaseUrl } from "./urls/base-url-builders";
import HomePage from "./components/HomePage.vue";
import { BASE_URL, REPOSITORY_ID } from "./injection-symbols";

export const init = (mount_point: HTMLElement): void => {
    const repository_id = Number.parseInt(getDatasetItemOrThrow(mount_point, "repositoryId"), 10);
    const project_id = Number.parseInt(getDatasetItemOrThrow(mount_point, "projectId"), 10);
    const base_url = buildBaseUrl(window.location, repository_id, project_id);

    createApp(HomePage)
        .provide(REPOSITORY_ID, repository_id)
        .provide(BASE_URL, base_url)
        .use(VueDOMPurifyHTML)
        .mount(mount_point);
};

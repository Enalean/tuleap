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
import { createGettext } from "vue3-gettext";
import { createPinia } from "pinia";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { getDatasetItemOrThrow } from "@tuleap/dom";
import MoveModal from "./components/MoveModal.vue";
import {
    ARTIFACT_ID,
    PROJECT_ID,
    TRACKER_COLOR,
    TRACKER_ID,
    TRACKER_NAME,
} from "./injection-symbols";

export async function init(vue_mount_point: HTMLElement): Promise<void> {
    createApp(MoveModal)
        .provide(
            TRACKER_ID,
            Number.parseInt(getDatasetItemOrThrow(vue_mount_point, "trackerId"), 10),
        )
        .provide(TRACKER_NAME, getDatasetItemOrThrow(vue_mount_point, "trackerName"))
        .provide(TRACKER_COLOR, getDatasetItemOrThrow(vue_mount_point, "trackerColor"))
        .provide(
            ARTIFACT_ID,
            Number.parseInt(getDatasetItemOrThrow(vue_mount_point, "artifactId"), 10),
        )
        .provide(
            PROJECT_ID,
            Number.parseInt(getDatasetItemOrThrow(vue_mount_point, "projectId"), 10),
        )
        .use(
            await initVueGettext(createGettext, (locale: string) => {
                return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
            }),
        )
        .use(createPinia())
        .mount(vue_mount_point);
}

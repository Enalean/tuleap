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

import VueDOMPurifyHTML from "vue-dompurify-html";
import { createInitializedStore } from "./store/index.js";
import StepDefinitionField from "./StepDefinitionField.vue";
import { createApp } from "vue";
import { getAttributeOrThrow } from "@tuleap/dom";
import { getPOFileFromLocale, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";
import {
    PROJECT_ID,
    FIELD_ID,
    EMPTY_STEP,
    UPLOAD_URL,
    UPLOAD_FIELD_NAME,
    UPLOAD_MAX_SIZE,
} from "./injection-keys.ts";

document.addEventListener("DOMContentLoaded", async () => {
    for (const mount_point of document.querySelectorAll(".ttm-definition-step-mount-point")) {
        const store = createInitializedStore();
        const initial_steps = JSON.parse(mount_point.dataset.steps);

        createApp(StepDefinitionField, {
            initial_steps,
        })
            .provide(PROJECT_ID, Number(getAttributeOrThrow(mount_point, "data-project-id")))
            .provide(FIELD_ID, Number(getAttributeOrThrow(mount_point, "data-field-id")))
            .provide(EMPTY_STEP, JSON.parse(getAttributeOrThrow(mount_point, "data-empty-step")))
            .provide(UPLOAD_URL, getAttributeOrThrow(mount_point, "data-upload-url"))
            .provide(UPLOAD_FIELD_NAME, getAttributeOrThrow(mount_point, "data-upload-field-name"))
            .provide(UPLOAD_MAX_SIZE, getAttributeOrThrow(mount_point, "data-upload-max-size"))
            .use(VueDOMPurifyHTML)
            .use(
                await initVueGettext(
                    createGettext,
                    (locale) => import(`../po/${getPOFileFromLocale(locale)}`),
                ),
            )
            .use(store)
            .mount(mount_point);
    }
});

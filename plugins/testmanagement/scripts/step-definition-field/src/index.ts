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

import { ref, createApp } from "vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { v4 as uuid } from "uuid";
import StepDefinitionField from "./StepDefinitionField.vue";
import { getAttributeOrThrow } from "@tuleap/dom";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";
import {
    PROJECT_ID,
    FIELD_ID,
    EMPTY_STEP,
    UPLOAD_URL,
    UPLOAD_FIELD_NAME,
    UPLOAD_MAX_SIZE,
    IS_DRAGGING,
    STEPS,
} from "./injection-keys";
import type { Step } from "./Step";

document.addEventListener("DOMContentLoaded", async () => {
    for (const mount_point of document.querySelectorAll(".ttm-definition-step-mount-point")) {
        if (!mount_point || !(mount_point instanceof HTMLElement)) {
            return;
        }
        const initial_steps = JSON.parse(getAttributeOrThrow(mount_point, "data-steps")).map(
            (step: Step) => {
                return { ...step, uuid: uuid(), is_deleted: false };
            },
        );

        const gettext_plugin = await initVueGettext(
            /** @ts-expect-error vue3-gettext-init is tested with Vue 3.4, but here we use Vue 3.5 */
            createGettext,
            (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
        );

        createApp(StepDefinitionField)
            .provide(PROJECT_ID, Number(getAttributeOrThrow(mount_point, "data-project-id")))
            .provide(FIELD_ID, Number(getAttributeOrThrow(mount_point, "data-field-id")))
            .provide(EMPTY_STEP, JSON.parse(getAttributeOrThrow(mount_point, "data-empty-step")))
            .provide(UPLOAD_URL, getAttributeOrThrow(mount_point, "data-upload-url"))
            .provide(UPLOAD_FIELD_NAME, getAttributeOrThrow(mount_point, "data-upload-field-name"))
            .provide(UPLOAD_MAX_SIZE, getAttributeOrThrow(mount_point, "data-upload-max-size"))
            .provide(IS_DRAGGING, ref(false))
            .provide(STEPS, ref(initial_steps))
            .use(VueDOMPurifyHTML)
            /** @ts-expect-error vue3-gettext-init is tested with Vue 3.4, but here we use Vue 3.5 */
            .use(gettext_plugin)
            .mount(mount_point);
    }
});

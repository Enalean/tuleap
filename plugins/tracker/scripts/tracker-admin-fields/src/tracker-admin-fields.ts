/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import { createApp } from "vue";
import { createGettext } from "vue3-gettext";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { getAttributeOrThrow } from "@tuleap/dom";
import FieldsUsage from "./components/FieldsUsage.vue";
import "./styles/tracker-admin-fields.scss";

document.addEventListener("DOMContentLoaded", async () => {
    const mount_point = document.getElementById("tracker-admin-fields-usage-mount-point");
    if (mount_point === null) {
        return;
    }

    createApp(FieldsUsage, {
        tracker_id: parseInt(getAttributeOrThrow(mount_point, "data-tracker-id"), 10),
        fields: JSON.parse(getAttributeOrThrow(mount_point, "data-fields")),
        structure: JSON.parse(getAttributeOrThrow(mount_point, "data-structure")),
    })
        .use(
            /** @ts-expect-error vue3-gettext-init is tested with Vue 3.4, but here we use Vue 3.5 */
            await initVueGettext(createGettext, (locale) => {
                return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
            }),
        )
        .use(VueDOMPurifyHTML)
        .mount(mount_point);
});

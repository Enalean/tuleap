/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { getAttributeOrThrow, selectOrThrow } from "@tuleap/dom";
import DeleteModal from "./components/DeleteModal.vue";

document.addEventListener("DOMContentLoaded", () => {
    const delete_link = selectOrThrow(document, "#tracker-action-button-delete");
    const vue_mount_point = selectOrThrow(document, "#delete-artifact-modal-mount-point");

    delete_link.addEventListener("click", async () => {
        createApp(DeleteModal, {
            artifact_id: Number.parseInt(
                getAttributeOrThrow(vue_mount_point, "data-artifact-id"),
                10,
            ),
            tracker_id: Number.parseInt(
                getAttributeOrThrow(vue_mount_point, "data-tracker-id"),
                10,
            ),
            token: getAttributeOrThrow(vue_mount_point, "data-csrf-token"),
            token_name: getAttributeOrThrow(vue_mount_point, "data-csrf-name"),
        })
            .use(
                /** @ts-expect-error vue3-gettext-init is tested with Vue 3.4, but here we use Vue 3.5 */
                await initVueGettext(
                    /** @ts-expect-error vue3-gettext-init is tested with Vue 3.4, but here we use Vue 3.5 */
                    createGettext,
                    (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
                ),
            )
            .mount(vue_mount_point);
    });
});

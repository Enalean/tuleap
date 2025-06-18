/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";
import { selectOrThrow } from "@tuleap/dom";
import BaseSiteAdminAddModal from "./components/BaseSiteAdminAddModal.vue";
import BaseSiteAdminEditModal from "./components/BaseSiteAdminEditModal.vue";
import { setupDeleteButtons } from "./setup-delete-buttons.js";
import { gatherConfiguration } from "./gather-configuration.js";

const ADD_BUTTON_SELECTOR = "#project-admin-services-add-button";
const ADD_MOUNT_POINT_SELECTOR = "#service-add-modal";
const EDIT_BUTTONS_SELECTOR = ".project-admin-services-edit-button";
const EDIT_MOUNT_POINT_SELECTOR = "#service-edit-modal";

document.addEventListener("DOMContentLoaded", async () => {
    const gettext_plugin = await initVueGettext(
        createGettext,
        (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );
    setupCreateServiceModal(gettext_plugin);
    setupEditServiceModals(gettext_plugin);
    setupDeleteButtons(gettext_plugin);
});

function setupCreateServiceModal(gettext_plugin) {
    const vue_mount_point = selectOrThrow(document, ADD_MOUNT_POINT_SELECTOR);

    const configuration = gatherConfiguration(vue_mount_point);
    const add_modal = createApp(BaseSiteAdminAddModal, configuration)
        .use(gettext_plugin)
        .mount(vue_mount_point);

    const add_button = selectOrThrow(document, ADD_BUTTON_SELECTOR, HTMLButtonElement);
    add_button.addEventListener("click", () => {
        add_modal.show();
    });
}

function setupEditServiceModals(gettext_plugin) {
    const vue_mount_point = selectOrThrow(document, EDIT_MOUNT_POINT_SELECTOR);

    const configuration = gatherConfiguration(vue_mount_point);
    const edit_modal = createApp(BaseSiteAdminEditModal, configuration)
        .use(gettext_plugin)
        .mount(vue_mount_point);

    const buttons = document.querySelectorAll(EDIT_BUTTONS_SELECTOR);
    for (const edit_button of buttons) {
        if (!edit_button.hasAttribute("data-service-json")) {
            throw new Error(`Could not find service JSON for edit service button`);
        }
        edit_button.addEventListener("click", () => {
            edit_modal.show(edit_button);
        });
    }
}

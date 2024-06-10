/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { initVueGettext, getPOFileFromLocaleWithoutExtension } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";
import App from "./App.vue";
import { createApp } from "vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { provideSectionsStore } from "@/stores/useSectionsStore";
import { sectionsStoreKey } from "@/stores/sectionsStoreKey";
import { CURRENT_LOCALE } from "@/locale-injection-key";
import { userLocale } from "@/helpers/user-locale";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { TITLE } from "@/title-injection-key";
import { getDatasetItemOrThrow } from "@tuleap/dom";
import { CONFIGURATION_STORE, initConfigurationStore } from "@/stores/configuration-store";
import {
    OPEN_CONFIGURATION_MODAL_BUS,
    useOpenConfigurationModalBus,
} from "@/composables/useOpenConfigurationModalBus";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { UPLOAD_MAX_SIZE } from "@/max-upload-size-injecion-keys";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("artidoc-mountpoint");
    if (vue_mount_point === null) {
        return;
    }

    let user_locale = "en_US";

    const gettext = await initVueGettext(createGettext, (locale: string) => {
        user_locale = locale;
        return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
    });

    const current_locale = userLocale(user_locale);

    const item_id = Number.parseInt(getDatasetItemOrThrow(vue_mount_point, "itemId"), 10);

    const app = createApp(App, { item_id });

    const sections_store = provideSectionsStore();
    app.provide(sectionsStoreKey, sections_store);
    app.provide(CURRENT_LOCALE, current_locale);
    app.provide(
        CAN_USER_EDIT_DOCUMENT,
        Boolean(getDatasetItemOrThrow(vue_mount_point, "canUserEditDocument")),
    );
    app.provide(OPEN_CONFIGURATION_MODAL_BUS, useOpenConfigurationModalBus());
    app.provide(DOCUMENT_ID, item_id);
    app.provide(TITLE, getDatasetItemOrThrow(vue_mount_point, "title"));
    app.provide(
        UPLOAD_MAX_SIZE,
        Number.parseInt(getDatasetItemOrThrow(vue_mount_point, "uploadMaxSize"), 10),
    );
    app.provide(
        CONFIGURATION_STORE,
        initConfigurationStore(
            item_id,
            JSON.parse(getDatasetItemOrThrow(vue_mount_point, "selectedTracker")),
            JSON.parse(getDatasetItemOrThrow(vue_mount_point, "allowedTrackers")),
            sections_store,
        ),
    );

    app.use(gettext);
    app.use(VueDOMPurifyHTML);
    app.mount(vue_mount_point);
});

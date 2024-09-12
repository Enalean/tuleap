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
import { useSectionsStore } from "@/stores/useSectionsStore";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";
import { CURRENT_LOCALE } from "@/locale-injection-key";
import { userLocale } from "@/helpers/user-locale";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { TITLE } from "@/title-injection-key";
import { getDatasetItemOrThrow } from "@tuleap/dom";
import { CONFIGURATION_STORE, initConfigurationStore } from "@/stores/configuration-store";
import {
    OPEN_CONFIGURATION_MODAL_BUS,
    useOpenConfigurationModalBusStore,
} from "@/stores/useOpenConfigurationModalBusStore";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { UPLOAD_MAX_SIZE } from "@/max-upload-size-injecion-keys";
import { preventPageLeave } from "@/helpers/on-before-unload";
import { EDITORS_COLLECTION, useSectionEditorsStore } from "@/stores/useSectionEditorsStore";
import { PDF_TEMPLATES_STORE, initPdfTemplatesStore } from "@/stores/pdf-templates-store";
import { IS_USER_ANONYMOUS } from "@/is-user-anonymous";
import { EDITOR_CHOICE, editorChoice } from "@/helpers/editor-choice";
import { useUploadFileStore } from "@/stores/useUploadFileStore";
import { UPLOAD_FILE_STORE } from "./stores/upload-file-store-injection-key";
import {
    OPEN_ADD_EXISTING_SECTION_MODAL_BUS,
    useOpenAddExistingSectionModalBus,
} from "@/composables/useOpenAddExistingSectionModalBus";

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

    const app = createApp(App);

    const sections_store = useSectionsStore();
    const upload_file_store = useUploadFileStore();
    const editors_store = useSectionEditorsStore();
    app.provide(EDITORS_COLLECTION, editors_store);
    app.provide(SECTIONS_STORE, sections_store);
    app.provide(UPLOAD_FILE_STORE, upload_file_store);
    app.provide(CURRENT_LOCALE, current_locale);
    app.provide(
        CAN_USER_EDIT_DOCUMENT,
        Boolean(getDatasetItemOrThrow(vue_mount_point, "canUserEditDocument")),
    );
    app.provide(
        EDITOR_CHOICE,
        editorChoice(Boolean(getDatasetItemOrThrow(vue_mount_point, "isNextGenEditorEnabled"))),
    );
    app.provide(OPEN_CONFIGURATION_MODAL_BUS, useOpenConfigurationModalBusStore());
    app.provide(OPEN_ADD_EXISTING_SECTION_MODAL_BUS, useOpenAddExistingSectionModalBus());
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
    app.provide(
        PDF_TEMPLATES_STORE,
        initPdfTemplatesStore(JSON.parse(getDatasetItemOrThrow(vue_mount_point, "pdfTemplates"))),
    );
    app.provide(IS_USER_ANONYMOUS, Number(getDatasetItemOrThrow(document.body, "userId")) === 0);

    app.use(gettext);
    app.use(VueDOMPurifyHTML);
    app.mount(vue_mount_point);

    preventPageLeave(editors_store);
});

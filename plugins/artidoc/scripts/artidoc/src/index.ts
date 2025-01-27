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
import { buildSectionsCollection } from "@/sections/SectionsCollection";
import { SECTIONS_COLLECTION } from "@/sections/sections-collection-injection-key";
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
import { PDF_TEMPLATES_STORE, initPdfTemplatesStore } from "@/stores/pdf-templates-store";
import { IS_USER_ANONYMOUS } from "@/is-user-anonymous";
import { useUploadFileStore } from "@/stores/useUploadFileStore";
import { UPLOAD_FILE_STORE } from "./stores/upload-file-store-injection-key";
import { NOTIFICATION_STORE } from "@/stores/notification-injection-key";
import { useNotificationsStore } from "@/stores/useNotificationsStore";
import {
    OPEN_ADD_EXISTING_SECTION_MODAL_BUS,
    useOpenAddExistingSectionModalBus,
} from "@/composables/useOpenAddExistingSectionModalBus";
import { TOOLBAR_BUS } from "@/toolbar-bus-injection-key";
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";
import {
    REMOVE_FREETEXT_SECTION_MODAL,
    useRemoveFreetextSectionModal,
} from "@/composables/useRemoveFreetextSectionModal";
import { watchForNeededPendingSectionInsertion } from "@/sections/PendingSectionInserter";
import { IS_FREETEXT_ALLOWED } from "@/is-freetext-allowed";
import { SECTIONS_STATES_COLLECTION } from "@/sections/sections-states-collection-injection-key";
import { getSectionsStatesCollection } from "@/sections/SectionsStatesCollection";
import { skeleton_sections_collection } from "@/helpers/get-skeleton-sections-collection";
import { getSectionStateBuilder } from "@/sections/SectionStateBuilder";

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

    const item_id = Number.parseInt(getDatasetItemOrThrow(vue_mount_point, "data-item-id"), 10);

    const app = createApp(App);

    app.provide(TOOLBAR_BUS, buildToolbarBus());

    const can_user_edit_document = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "data-can-user-edit-document"),
    );
    const selected_tracker = JSON.parse(
        getDatasetItemOrThrow(vue_mount_point, "data-selected-tracker"),
    );
    const upload_file_store = useUploadFileStore();
    const states_collection = getSectionsStatesCollection(
        getSectionStateBuilder(can_user_edit_document, upload_file_store.pending_uploads),
    );
    const sections_collection = buildSectionsCollection(states_collection);
    const notifications_store = useNotificationsStore();

    sections_collection.replaceAll(skeleton_sections_collection);

    const configuration_store = initConfigurationStore(
        item_id,
        selected_tracker,
        JSON.parse(getDatasetItemOrThrow(vue_mount_point, "data-allowed-trackers")),
    );

    watchForNeededPendingSectionInsertion(
        sections_collection,
        states_collection,
        configuration_store.selected_tracker,
        can_user_edit_document,
    );

    app.provide(SECTIONS_COLLECTION, sections_collection);
    app.provide(SECTIONS_STATES_COLLECTION, states_collection);
    app.provide(UPLOAD_FILE_STORE, upload_file_store);
    app.provide(NOTIFICATION_STORE, notifications_store);
    app.provide(CURRENT_LOCALE, current_locale);
    app.provide(CAN_USER_EDIT_DOCUMENT, can_user_edit_document);
    app.provide(OPEN_CONFIGURATION_MODAL_BUS, useOpenConfigurationModalBusStore());
    app.provide(OPEN_ADD_EXISTING_SECTION_MODAL_BUS, useOpenAddExistingSectionModalBus());
    app.provide(REMOVE_FREETEXT_SECTION_MODAL, useRemoveFreetextSectionModal());
    app.provide(DOCUMENT_ID, item_id);
    app.provide(TITLE, getDatasetItemOrThrow(vue_mount_point, "data-title"));
    app.provide(
        UPLOAD_MAX_SIZE,
        Number.parseInt(getDatasetItemOrThrow(vue_mount_point, "data-upload-max-size"), 10),
    );
    app.provide(
        IS_FREETEXT_ALLOWED,
        Number.parseInt(getDatasetItemOrThrow(vue_mount_point, "data-is-freetext-allowed"), 10),
    );
    app.provide(CONFIGURATION_STORE, configuration_store);
    app.provide(
        PDF_TEMPLATES_STORE,
        initPdfTemplatesStore(
            JSON.parse(getDatasetItemOrThrow(vue_mount_point, "data-pdf-templates")),
        ),
    );
    app.provide(
        IS_USER_ANONYMOUS,
        Number(getDatasetItemOrThrow(document.body, "data-user-id")) === 0,
    );

    app.use(gettext);
    app.use(VueDOMPurifyHTML);
    app.mount(vue_mount_point);

    preventPageLeave(states_collection);
});

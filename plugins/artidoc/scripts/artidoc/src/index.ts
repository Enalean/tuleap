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
import { createApp, ref } from "vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { createGettext } from "vue3-gettext";
import { getDatasetItemOrThrow } from "@tuleap/dom";
import App from "./App.vue";

import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import { CURRENT_LOCALE } from "@/locale-injection-key";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";
import { TITLE } from "@/title-injection-key";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { UPLOAD_MAX_SIZE } from "@/max-upload-size-injecion-keys";
import { IS_USER_ANONYMOUS } from "@/is-user-anonymous";
import { NOTIFICATION_COLLECTION } from "@/sections/notifications/notification-collection-injection-key";
import { TOOLBAR_BUS } from "@/toolbar-bus-injection-key";
import { IS_FREETEXT_ALLOWED } from "@/is-freetext-allowed";
import { SECTIONS_STATES_COLLECTION } from "@/sections/states/sections-states-collection-injection-key";
import { FILE_UPLOADS_COLLECTION } from "@/sections/attachments/sections-file-uploads-collection-injection-key";
import { CONFIGURATION_STORE, initConfigurationStore } from "@/stores/configuration-store";
import { PDF_TEMPLATES_STORE, initPdfTemplatesStore } from "@/stores/pdf-templates-store";
import {
    OPEN_CONFIGURATION_MODAL_BUS,
    useOpenConfigurationModalBusStore,
} from "@/stores/useOpenConfigurationModalBusStore";
import {
    OPEN_ADD_EXISTING_SECTION_MODAL_BUS,
    useOpenAddExistingSectionModalBus,
} from "@/composables/useOpenAddExistingSectionModalBus";
import {
    REMOVE_FREETEXT_SECTION_MODAL,
    useRemoveFreetextSectionModal,
} from "@/composables/useRemoveFreetextSectionModal";

import { buildSectionsCollection } from "@/sections/SectionsCollection";
import { userLocale } from "@/helpers/user-locale";
import { preventPageLeave } from "@/helpers/on-before-unload";
import { getFileUploadsCollection } from "@/sections/attachments/FileUploadsCollection";
import { buildNotificationsCollection } from "@/sections/notifications/NotificationsCollection";
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";
import { watchForNeededPendingSectionInsertion } from "@/sections/insert/PendingSectionInserter";
import { getSectionsStatesCollection } from "@/sections/states/SectionsStatesCollection";
import { getSectionStateBuilder } from "@/sections/states/SectionStateBuilder";
import { skeleton_sections_collection } from "@/helpers/get-skeleton-sections-collection";
import { PROJECT_ID } from "@/project-id-injection-key";
import { IS_LOADING_SECTIONS_FAILED } from "@/is-loading-sections-injection-key";

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
    const file_uploads_collection = getFileUploadsCollection();
    const states_collection = getSectionsStatesCollection(
        getSectionStateBuilder(can_user_edit_document, file_uploads_collection.pending_uploads),
    );
    const sections_collection = buildSectionsCollection(states_collection);
    sections_collection.replaceAll(skeleton_sections_collection);

    const configuration_store = initConfigurationStore(
        item_id,
        selected_tracker,
        JSON.parse(getDatasetItemOrThrow(vue_mount_point, "data-allowed-trackers")),
    );

    const is_loading_failed = ref(false);

    watchForNeededPendingSectionInsertion(
        sections_collection,
        states_collection,
        configuration_store.selected_tracker,
        can_user_edit_document,
        is_loading_failed,
    );

    app.provide(SECTIONS_COLLECTION, sections_collection);
    app.provide(SECTIONS_STATES_COLLECTION, states_collection);
    app.provide(FILE_UPLOADS_COLLECTION, file_uploads_collection);
    app.provide(NOTIFICATION_COLLECTION, buildNotificationsCollection());
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

    app.provide(PROJECT_ID, getDatasetItemOrThrow(vue_mount_point, "data-project-id"));
    app.provide(IS_LOADING_SECTIONS_FAILED, is_loading_failed);
    app.use(gettext);
    app.use(VueDOMPurifyHTML);
    app.mount(vue_mount_point);

    preventPageLeave(states_collection);
});

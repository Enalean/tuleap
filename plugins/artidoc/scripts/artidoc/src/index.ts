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

import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createApp, ref } from "vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { createGettext } from "vue3-gettext";
import { getAttributeOrThrow } from "@tuleap/dom";
import { Option } from "@tuleap/option";
import App from "./App.vue";

import { SECTIONS_COLLECTION } from "@/sections/states/sections-collection-injection-key";
import { buildUserPreferences, USER_PREFERENCES } from "@/user-preferences-injection-key";
import {
    CAN_USER_EDIT_DOCUMENT,
    ORIGINAL_CAN_USER_EDIT_DOCUMENT,
} from "@/can-user-edit-document-injection-key";
import { TITLE } from "@/title-injection-key";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import { UPLOAD_MAX_SIZE } from "@/max-upload-size-injecion-keys";
import { IS_USER_ANONYMOUS } from "@/is-user-anonymous";
import { NOTIFICATION_COLLECTION } from "@/sections/notifications/notification-collection-injection-key";
import { TOOLBAR_BUS } from "@/toolbar-bus-injection-key";
import { SECTIONS_STATES_COLLECTION } from "@/sections/states/sections-states-collection-injection-key";
import { FILE_UPLOADS_COLLECTION } from "@/sections/attachments/sections-file-uploads-collection-injection-key";
import {
    buildPdfTemplatesCollection,
    PDF_TEMPLATES_COLLECTION,
} from "@/pdf/pdf-templates-collection";
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
import { HEADINGS_BUTTON_STATE } from "@/headings-button-state-injection-key";
import { getHeadingsButtonState } from "@/toolbar/HeadingsButtonState";
import { watchUpdateSectionsLevels } from "@/sections/levels/SectionsNumbersWatcher";
import { getSectionsNumberer } from "@/sections/levels/SectionsNumberer";
import type { Tracker } from "@/configuration/AllowedTrackersCollection";
import {
    ALLOWED_TRACKERS,
    buildAllowedTrackersCollection,
} from "@/configuration/AllowedTrackersCollection";
import { buildSelectedTracker, SELECTED_TRACKER } from "@/configuration/SelectedTracker";
import {
    buildSelectedFieldsCollection,
    SELECTED_FIELDS,
} from "@/configuration/SelectedFieldsCollection";
import {
    AVAILABLE_FIELDS,
    buildAvailableFieldsCollection,
} from "@/configuration/AvailableFieldsCollection";
import {
    ARE_VERSIONS_DISPLAYED,
    CAN_USER_DISPLAY_VERSIONS,
} from "@/can-user-display-versions-injection-key";
import { USE_FAKE_VERSIONS } from "@/use-fake-versions-injection-key";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("artidoc-mountpoint");
    if (vue_mount_point === null) {
        return;
    }

    const gettext = await initVueGettext(createGettext, (locale: string) => {
        return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
    });

    const item_id = Number.parseInt(getAttributeOrThrow(vue_mount_point, "data-item-id"), 10);

    const app = createApp(App);

    app.provide(TOOLBAR_BUS, buildToolbarBus());
    app.provide(HEADINGS_BUTTON_STATE, getHeadingsButtonState());

    const original_can_user_edit_document = Boolean(
        getAttributeOrThrow(vue_mount_point, "data-can-user-edit-document"),
    );
    const can_user_edit_document = ref(original_can_user_edit_document);
    const can_user_display_versions = Boolean(
        getAttributeOrThrow(vue_mount_point, "data-can-user-display-versions"),
    );
    const saved_tracker = Option.fromNullable<Tracker>(
        JSON.parse(getAttributeOrThrow(vue_mount_point, "data-selected-tracker")),
    );
    const file_uploads_collection = getFileUploadsCollection();
    const states_collection = getSectionsStatesCollection(
        getSectionStateBuilder(can_user_edit_document, file_uploads_collection.pending_uploads),
    );
    const sections_collection = buildSectionsCollection(states_collection);
    sections_collection.replaceAll(skeleton_sections_collection);

    const allowed_trackers = buildAllowedTrackersCollection(
        JSON.parse(getAttributeOrThrow(vue_mount_point, "data-allowed-trackers")),
    );
    const selected_tracker = buildSelectedTracker(saved_tracker);
    const selected_fields = buildSelectedFieldsCollection(
        JSON.parse(getAttributeOrThrow(vue_mount_point, "data-selected-fields")),
    );
    const available_fields = await buildAvailableFieldsCollection(
        selected_tracker,
        selected_fields,
    );

    const is_loading_failed = ref(false);

    watchForNeededPendingSectionInsertion(
        sections_collection,
        states_collection,
        selected_tracker,
        original_can_user_edit_document,
        is_loading_failed,
    );

    watchUpdateSectionsLevels(sections_collection, getSectionsNumberer(sections_collection));

    app.provide(SECTIONS_COLLECTION, sections_collection)
        .provide(SECTIONS_STATES_COLLECTION, states_collection)
        .provide(FILE_UPLOADS_COLLECTION, file_uploads_collection)
        .provide(NOTIFICATION_COLLECTION, buildNotificationsCollection())
        .provide(ORIGINAL_CAN_USER_EDIT_DOCUMENT, original_can_user_edit_document)
        .provide(CAN_USER_EDIT_DOCUMENT, can_user_edit_document)
        .provide(CAN_USER_DISPLAY_VERSIONS, can_user_display_versions)
        .provide(ARE_VERSIONS_DISPLAYED, ref(false))
        .provide(OPEN_CONFIGURATION_MODAL_BUS, useOpenConfigurationModalBusStore())
        .provide(OPEN_ADD_EXISTING_SECTION_MODAL_BUS, useOpenAddExistingSectionModalBus())
        .provide(REMOVE_FREETEXT_SECTION_MODAL, useRemoveFreetextSectionModal())
        .provide(DOCUMENT_ID, item_id)
        .provide(TITLE, getAttributeOrThrow(vue_mount_point, "data-title"))
        .provide(
            UPLOAD_MAX_SIZE,
            Number.parseInt(getAttributeOrThrow(vue_mount_point, "data-upload-max-size"), 10),
        )
        .provide(ALLOWED_TRACKERS, allowed_trackers)
        .provide(SELECTED_TRACKER, selected_tracker)
        .provide(SELECTED_FIELDS, selected_fields)
        .provide(AVAILABLE_FIELDS, available_fields)
        .provide(
            PDF_TEMPLATES_COLLECTION,
            buildPdfTemplatesCollection(
                JSON.parse(getAttributeOrThrow(vue_mount_point, "data-pdf-templates")),
            ),
        )
        .provide(
            IS_USER_ANONYMOUS,
            Number(getAttributeOrThrow(document.body, "data-user-id")) === 0,
        )
        .provide(
            PROJECT_ID,
            Number.parseInt(getAttributeOrThrow(vue_mount_point, "data-project-id"), 10),
        )
        .provide(IS_LOADING_SECTIONS_FAILED, is_loading_failed)
        .provide(USER_PREFERENCES, buildUserPreferences(document, vue_mount_point))
        .provide(USE_FAKE_VERSIONS, ref(true))
        .use(gettext)
        .use(VueDOMPurifyHTML)
        .mount(vue_mount_point);

    preventPageLeave(states_collection);
});

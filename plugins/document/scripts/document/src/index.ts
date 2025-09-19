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

import "../themes/document.scss";
import { createApp } from "vue";
import VueDOMPurifyHTML from "vue-dompurify-html";

import App from "./components/App.vue";
import { createInitializedStore } from "./store";
import { createInitializedRouter } from "./router/router";
import moment from "moment";
import "moment-timezone";
import { createPinia } from "pinia";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";
import { getAttributeOrThrow } from "@tuleap/dom";

import { setupDocumentShortcuts } from "./keyboard-navigation/keyboard-navigation";
import {
    NEW_ITEMS_ALTERNATIVES,
    OTHER_ITEM_TYPES,
    SHOULD_DISPLAY_SOURCE_COLUMN_FOR_VERSIONS,
} from "./injection-keys";
import type { SearchCriterion, SearchListOption } from "./type";
import { getRelativeDateUserPreferenceOrThrow } from "@tuleap/tlp-relative-date";
import {
    CAN_USER_SWITCH_TO_OLD_UI,
    DATE_TIME_FORMAT,
    EMBEDDED_ARE_ALLOWED,
    FILENAME_PATTERN,
    FORBID_WRITERS_TO_DELETE,
    FORBID_WRITERS_TO_UPDATE,
    IS_CHANGELOG_PROPOSED_AFTER_DND,
    IS_DELETION_ALLOWED,
    IS_FILENAME_PATTERN_ENFORCED,
    IS_OBSOLESCENCE_DATE_PROPERTY_USED,
    IS_STATUS_PROPERTY_USED,
    MAX_ARCHIVE_SIZE,
    MAX_FILES_DRAGNDROP,
    MAX_SIZE_UPLOAD,
    PROJECT,
    RELATIVE_DATES_DISPLAY,
    ROOT_ID,
    SEARCH_COLUMNS,
    SEARCH_CRITERIA,
    USER_CAN_CREATE_WIKI,
    USER_CAN_DRAGNDROP,
    USER_ID,
    USER_IS_ADMIN,
    USER_LOCALE,
    WARNING_THRESHOLD,
} from "./configuration-keys";

interface MustacheCriterion {
    readonly name: string;
    readonly label: string;
    readonly type: "date" | "list" | "string";
    readonly options: ReadonlyArray<SearchListOption>;
}

document.addEventListener("DOMContentLoaded", async () => {
    let user_locale = document.body.dataset.userLocale ?? "en_US";
    user_locale = user_locale.replace(/_/g, "-");

    const vue_mount_point = document.getElementById("document-tree-view");

    if (!vue_mount_point) {
        return;
    }

    const project_id = Number.parseInt(getAttributeOrThrow(vue_mount_point, "data-project-id"), 10);
    const root_id = Number.parseInt(getAttributeOrThrow(vue_mount_point, "data-root-id"), 10);
    const project_name = getAttributeOrThrow(vue_mount_point, "data-project-name");
    const project_public_name = getAttributeOrThrow(vue_mount_point, "data-project-public-name");
    const project_url = getAttributeOrThrow(vue_mount_point, "data-project-url");
    const user_is_admin = Boolean(getAttributeOrThrow(vue_mount_point, "data-user-is-admin"));
    const user_can_create_wiki = Boolean(
        getAttributeOrThrow(vue_mount_point, "data-user-can-create-wiki"),
    );
    const user_timezone = getAttributeOrThrow(document.body, "data-user-timezone");
    const date_time_format = getAttributeOrThrow(document.body, "data-date-time-format");
    const user_id = Number.parseInt(getAttributeOrThrow(document.body, "data-user-id"), 10);
    const max_files_dragndrop = Number.parseInt(
        getAttributeOrThrow(vue_mount_point, "data-max-files-dragndrop"),
        10,
    );
    const max_size_upload = Number.parseInt(
        getAttributeOrThrow(vue_mount_point, "data-max-size-upload"),
        10,
    );
    const warning_threshold = Number.parseInt(
        getAttributeOrThrow(vue_mount_point, "data-warning-threshold"),
        10,
    );
    const max_archive_size = Number.parseInt(
        getAttributeOrThrow(vue_mount_point, "data-max-archive-size"),
        10,
    );
    const embedded_are_allowed = Boolean(
        getAttributeOrThrow(vue_mount_point, "data-embedded-are-allowed"),
    );
    const is_deletion_allowed = Boolean(
        getAttributeOrThrow(vue_mount_point, "data-user-can-delete-item"),
    );
    const is_status_property_used = Boolean(
        getAttributeOrThrow(vue_mount_point, "data-is-item-status-property-used"),
    );
    const forbid_writers_to_update = Boolean(
        getAttributeOrThrow(vue_mount_point, "data-forbid-writers-to-update"),
    );
    const forbid_writers_to_delete = Boolean(
        getAttributeOrThrow(vue_mount_point, "data-forbid-writers-to-delete"),
    );
    const is_obsolescence_date_property_used = Boolean(
        getAttributeOrThrow(vue_mount_point, "data-is-obsolescence-date-property-used"),
    );
    const is_changelog_proposed_after_dnd = Boolean(
        getAttributeOrThrow(vue_mount_point, "data-is-changelog-displayed-after-dnd"),
    );
    const csrf_token_name = getAttributeOrThrow(vue_mount_point, "data-csrf-token-name");
    const csrf_token = getAttributeOrThrow(vue_mount_point, "data-csrf-token");
    const relative_dates_display = getRelativeDateUserPreferenceOrThrow(
        vue_mount_point,
        "data-relative-dates-display",
    );
    const project_privacy = JSON.parse(getAttributeOrThrow(vue_mount_point, "data-privacy"));
    const project_flags = JSON.parse(getAttributeOrThrow(vue_mount_point, "data-project-flags"));
    const project_icon = getAttributeOrThrow(vue_mount_point, "data-project-icon");
    const filename_pattern = getAttributeOrThrow(vue_mount_point, "data-filename-pattern");
    const is_filename_pattern_enforced = Boolean(
        getAttributeOrThrow(vue_mount_point, "data-is-filename-pattern-enforced"),
    );
    const can_user_switch_to_old_ui = Boolean(
        getAttributeOrThrow(vue_mount_point, "data-can-user-switch-to-old-ui"),
    );
    const should_display_source_column_for_versions = Boolean(
        getAttributeOrThrow(vue_mount_point, "data-should-display-source-column"),
    );

    const consider_string_criteria_as_text = (criterion: MustacheCriterion): SearchCriterion => ({
        ...criterion,
        type: criterion.type === "string" ? "text" : criterion.type,
    });

    const search_criteria = JSON.parse(getAttributeOrThrow(vue_mount_point, "data-criteria")).map(
        consider_string_criteria_as_text,
    );
    const search_columns = JSON.parse(getAttributeOrThrow(vue_mount_point, "data-columns"));
    const create_new_item_alternatives = JSON.parse(
        getAttributeOrThrow(vue_mount_point, "data-create-new-item-alternatives"),
    );
    const other_item_types = JSON.parse(
        getAttributeOrThrow(vue_mount_point, "data-other-item-types"),
    );

    moment.tz(user_timezone);
    moment.locale(user_locale);

    const app = createApp(App, {
        csrf_token_name,
        csrf_token,
    });

    const store = createInitializedStore();
    app.use(store);
    const gettext = await initVueGettext(
        createGettext,
        (locale) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    const pinia = createPinia();
    app.use(pinia);

    app.use(gettext);
    app.use(
        createInitializedRouter(store, project_name, gettext.$gettext, root_id, search_criteria),
    );

    app.provide(
        SHOULD_DISPLAY_SOURCE_COLUMN_FOR_VERSIONS,
        should_display_source_column_for_versions,
    );
    app.provide(NEW_ITEMS_ALTERNATIVES, create_new_item_alternatives);
    app.provide(OTHER_ITEM_TYPES, other_item_types);
    app.provide(USER_ID, user_id)
        .provide(PROJECT, {
            id: project_id,
            name: project_name,
            public_name: project_public_name,
            url: project_url,
            privacy: project_privacy,
            flags: project_flags,
            icon: project_icon,
        })
        .provide(ROOT_ID, root_id)
        .provide(USER_IS_ADMIN, user_is_admin)
        .provide(USER_CAN_CREATE_WIKI, user_can_create_wiki)
        .provide(EMBEDDED_ARE_ALLOWED, embedded_are_allowed)
        .provide(IS_STATUS_PROPERTY_USED, is_status_property_used)
        .provide(IS_OBSOLESCENCE_DATE_PROPERTY_USED, is_obsolescence_date_property_used)
        .provide(MAX_FILES_DRAGNDROP, max_files_dragndrop)
        .provide(USER_CAN_DRAGNDROP, max_files_dragndrop > 0)
        .provide(MAX_SIZE_UPLOAD, max_size_upload)
        .provide(WARNING_THRESHOLD, warning_threshold)
        .provide(MAX_ARCHIVE_SIZE, max_archive_size)
        .provide(DATE_TIME_FORMAT, date_time_format)
        .provide(IS_CHANGELOG_PROPOSED_AFTER_DND, is_changelog_proposed_after_dnd)
        .provide(IS_DELETION_ALLOWED, is_deletion_allowed)
        .provide(USER_LOCALE, user_locale)
        .provide(RELATIVE_DATES_DISPLAY, relative_dates_display)
        .provide(SEARCH_CRITERIA, search_criteria)
        .provide(SEARCH_COLUMNS, search_columns)
        .provide(FORBID_WRITERS_TO_UPDATE, forbid_writers_to_update)
        .provide(FORBID_WRITERS_TO_DELETE, forbid_writers_to_delete)
        .provide(FILENAME_PATTERN, filename_pattern)
        .provide(IS_FILENAME_PATTERN_ENFORCED, is_filename_pattern_enforced)
        .provide(CAN_USER_SWITCH_TO_OLD_UI, can_user_switch_to_old_ui);
    app.use(VueDOMPurifyHTML);

    app.mount(vue_mount_point);

    setupDocumentShortcuts(gettext);
});

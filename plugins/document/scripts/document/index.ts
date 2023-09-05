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

import { createApp } from "vue";
import VueDOMPurifyHTML from "vue-dompurify-html";

import App from "./components/App.vue";
import { createInitializedStore } from "./store";
import { createInitializedRouter } from "./router/router";
import moment from "moment";
import "moment-timezone";
import { createPinia } from "pinia";
import { getPOFileFromLocale, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createGettext } from "vue3-gettext";
import { getDatasetItemOrThrow } from "@tuleap/dom";

import { setupDocumentShortcuts } from "./keyboard-navigation/keyboard-navigation";
import {
    NEW_ITEMS_ALTERNATIVES,
    SHOULD_DISPLAY_HISTORY_IN_DOCUMENT,
    SHOULD_DISPLAY_SOURCE_COLUMN_FOR_VERSIONS,
} from "./injection-keys";
import type { ConfigurationState } from "./store/configuration";
import type { SearchCriterion, SearchListOption } from "./type";

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

    const project_id = getDatasetItemOrThrow(vue_mount_point, "projectId");
    const root_id = Number.parseInt(getDatasetItemOrThrow(vue_mount_point, "rootId"), 10);
    const project_name = getDatasetItemOrThrow(vue_mount_point, "projectName");
    const project_public_name = getDatasetItemOrThrow(vue_mount_point, "projectPublicName");
    const project_url = getDatasetItemOrThrow(vue_mount_point, "projectUrl");
    const user_is_admin = Boolean(getDatasetItemOrThrow(vue_mount_point, "userIsAdmin"));
    const user_can_create_wiki = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "userCanCreateWiki"),
    );
    const user_timezone = getDatasetItemOrThrow(document.body, "userTimezone");
    const date_time_format = getDatasetItemOrThrow(document.body, "dateTimeFormat");
    const user_id = Number.parseInt(getDatasetItemOrThrow(document.body, "userId"), 10);
    const max_files_dragndrop = Number.parseInt(
        getDatasetItemOrThrow(vue_mount_point, "maxFilesDragndrop"),
        10,
    );
    const max_size_upload = Number.parseInt(
        getDatasetItemOrThrow(vue_mount_point, "maxSizeUpload"),
        10,
    );
    const warning_threshold = Number.parseInt(
        getDatasetItemOrThrow(vue_mount_point, "warningThreshold"),
        10,
    );
    const max_archive_size = Number.parseInt(
        getDatasetItemOrThrow(vue_mount_point, "maxArchiveSize"),
        10,
    );
    const embedded_are_allowed = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "embeddedAreAllowed"),
    );
    const is_deletion_allowed = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "userCanDeleteItem"),
    );
    const is_status_property_used = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "isItemStatusPropertyUsed"),
    );
    const forbid_writers_to_update = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "forbidWritersToUpdate"),
    );
    const forbid_writers_to_delete = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "forbidWritersToDelete"),
    );
    const is_obsolescence_date_property_used = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "isObsolescenceDatePropertyUsed"),
    );
    const is_changelog_proposed_after_dnd = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "isChangelogDisplayedAfterDnd"),
    );
    const csrf_token_name = getDatasetItemOrThrow(vue_mount_point, "csrfTokenName");
    const csrf_token = getDatasetItemOrThrow(vue_mount_point, "csrfToken");
    const relative_dates_display = getDatasetItemOrThrow(vue_mount_point, "relativeDatesDisplay");
    const privacy = JSON.parse(getDatasetItemOrThrow(vue_mount_point, "privacy"));
    const project_flags = JSON.parse(getDatasetItemOrThrow(vue_mount_point, "projectFlags"));
    const project_icon = getDatasetItemOrThrow(vue_mount_point, "projectIcon");
    const filename_pattern = getDatasetItemOrThrow(vue_mount_point, "filenamePattern");
    const is_filename_pattern_enforced = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "isFilenamePatternEnforced"),
    );
    const can_user_switch_to_old_ui = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "canUserSwitchToOldUi"),
    );
    const should_display_history_in_document = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "shouldDisplayHistoryInDocument"),
    );
    const should_display_source_column_for_versions = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "shouldDisplaySourceColumn"),
    );

    const consider_string_criteria_as_text = (criterion: MustacheCriterion): SearchCriterion => ({
        ...criterion,
        type: criterion.type === "string" ? "text" : criterion.type,
    });

    const criteria = JSON.parse(getDatasetItemOrThrow(vue_mount_point, "criteria")).map(
        consider_string_criteria_as_text,
    );
    const columns = JSON.parse(getDatasetItemOrThrow(vue_mount_point, "columns"));
    const create_new_item_alternatives = JSON.parse(
        getDatasetItemOrThrow(vue_mount_point, "createNewItemAlternatives"),
    );

    moment.tz(user_timezone);
    moment.locale(user_locale);

    const app = createApp(App, {
        csrf_token_name,
        csrf_token,
    });

    const configuration_state: ConfigurationState = {
        user_id,
        project_id,
        root_id,
        project_name,
        project_public_name,
        user_is_admin,
        user_can_create_wiki,
        embedded_are_allowed,
        is_status_property_used,
        is_obsolescence_date_property_used,
        project_url,
        date_time_format,
        max_files_dragndrop,
        max_size_upload,
        warning_threshold,
        max_archive_size,
        is_deletion_allowed,
        is_changelog_proposed_after_dnd,
        privacy,
        project_flags,
        relative_dates_display,
        project_icon,
        user_locale,
        criteria,
        columns,
        forbid_writers_to_update,
        forbid_writers_to_delete,
        filename_pattern,
        is_filename_pattern_enforced,
        can_user_switch_to_old_ui,
    };

    const store = createInitializedStore(user_id, project_id, configuration_state);
    app.use(store);
    const gettext = await initVueGettext(
        createGettext,
        (locale) => import(`./po/${getPOFileFromLocale(locale)}`),
    );

    const pinia = createPinia();
    app.use(pinia);

    app.use(gettext);
    app.use(createInitializedRouter(store, project_name, gettext.$gettext));

    app.provide(SHOULD_DISPLAY_HISTORY_IN_DOCUMENT, should_display_history_in_document);
    app.provide(
        SHOULD_DISPLAY_SOURCE_COLUMN_FOR_VERSIONS,
        should_display_source_column_for_versions,
    );
    app.provide(NEW_ITEMS_ALTERNATIVES, create_new_item_alternatives);
    app.use(VueDOMPurifyHTML);

    app.mount(vue_mount_point);

    setupDocumentShortcuts(gettext);
});

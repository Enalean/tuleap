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
    OTHER_ITEM_TYPES,
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

    const project_id = Number.parseInt(
        getDatasetItemOrThrow(vue_mount_point, "data-project-id"),
        10,
    );
    const root_id = Number.parseInt(getDatasetItemOrThrow(vue_mount_point, "data-root-id"), 10);
    const project_name = getDatasetItemOrThrow(vue_mount_point, "data-project-name");
    const project_public_name = getDatasetItemOrThrow(vue_mount_point, "data-project-public-name");
    const project_url = getDatasetItemOrThrow(vue_mount_point, "data-project-url");
    const user_is_admin = Boolean(getDatasetItemOrThrow(vue_mount_point, "data-user-is-admin"));
    const user_can_create_wiki = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "data-user-can-create-wiki"),
    );
    const user_timezone = getDatasetItemOrThrow(document.body, "data-user-timezone");
    const date_time_format = getDatasetItemOrThrow(document.body, "data-date-time-format");
    const user_id = Number.parseInt(getDatasetItemOrThrow(document.body, "data-user-id"), 10);
    const max_files_dragndrop = Number.parseInt(
        getDatasetItemOrThrow(vue_mount_point, "data-max-files-dragndrop"),
        10,
    );
    const max_size_upload = Number.parseInt(
        getDatasetItemOrThrow(vue_mount_point, "data-max-size-upload"),
        10,
    );
    const warning_threshold = Number.parseInt(
        getDatasetItemOrThrow(vue_mount_point, "data-warning-threshold"),
        10,
    );
    const max_archive_size = Number.parseInt(
        getDatasetItemOrThrow(vue_mount_point, "data-max-archive-size"),
        10,
    );
    const embedded_are_allowed = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "data-embedded-are-allowed"),
    );
    const is_deletion_allowed = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "data-user-can-delete-item"),
    );
    const is_status_property_used = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "data-is-item-status-property-used"),
    );
    const forbid_writers_to_update = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "data-forbid-writers-to-update"),
    );
    const forbid_writers_to_delete = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "data-forbid-writers-to-delete"),
    );
    const is_obsolescence_date_property_used = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "data-is-obsolescence-date-property-used"),
    );
    const is_changelog_proposed_after_dnd = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "data-is-changelog-displayed-after-dnd"),
    );
    const csrf_token_name = getDatasetItemOrThrow(vue_mount_point, "data-csrf-token-name");
    const csrf_token = getDatasetItemOrThrow(vue_mount_point, "data-csrf-token");
    const relative_dates_display = getDatasetItemOrThrow(
        vue_mount_point,
        "data-relative-dates-display",
    );
    const privacy = JSON.parse(getDatasetItemOrThrow(vue_mount_point, "data-privacy"));
    const project_flags = JSON.parse(getDatasetItemOrThrow(vue_mount_point, "data-project-flags"));
    const project_icon = getDatasetItemOrThrow(vue_mount_point, "data-project-icon");
    const filename_pattern = getDatasetItemOrThrow(vue_mount_point, "data-filename-pattern");
    const is_filename_pattern_enforced = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "data-is-filename-pattern-enforced"),
    );
    const can_user_switch_to_old_ui = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "data-can-user-switch-to-old-ui"),
    );
    const should_display_source_column_for_versions = Boolean(
        getDatasetItemOrThrow(vue_mount_point, "data-should-display-source-column"),
    );

    const consider_string_criteria_as_text = (criterion: MustacheCriterion): SearchCriterion => ({
        ...criterion,
        type: criterion.type === "string" ? "text" : criterion.type,
    });

    const criteria = JSON.parse(getDatasetItemOrThrow(vue_mount_point, "data-criteria")).map(
        consider_string_criteria_as_text,
    );
    const columns = JSON.parse(getDatasetItemOrThrow(vue_mount_point, "data-columns"));
    const create_new_item_alternatives = JSON.parse(
        getDatasetItemOrThrow(vue_mount_point, "data-create-new-item-alternatives"),
    );
    const other_item_types = JSON.parse(
        getDatasetItemOrThrow(vue_mount_point, "data-other-item-types"),
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

    app.provide(
        SHOULD_DISPLAY_SOURCE_COLUMN_FOR_VERSIONS,
        should_display_source_column_for_versions,
    );
    app.provide(NEW_ITEMS_ALTERNATIVES, create_new_item_alternatives);
    app.provide(OTHER_ITEM_TYPES, other_item_types);
    app.use(VueDOMPurifyHTML);

    app.mount(vue_mount_point);

    setupDocumentShortcuts(gettext);
});

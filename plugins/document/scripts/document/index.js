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

import Vue from "vue";
import GetTextPlugin from "vue-gettext";
import VueDOMPurifyHTML from "vue-dompurify-html";

import french_translations from "./po/fr.po";
import App from "./components/App.vue";
import { createStore } from "./store/index.js";
import { createRouter } from "./router/index.js";
import moment from "moment";
import "moment-timezone";

document.addEventListener("DOMContentLoaded", () => {
    Vue.use(VueDOMPurifyHTML);
    Vue.use(GetTextPlugin, {
        translations: {
            fr: french_translations.messages,
        },
        silent: true,
    });

    let user_locale = document.body.dataset.userLocale;
    Vue.config.language = user_locale;
    user_locale = user_locale.replace(/_/g, "-");

    const vue_mount_point = document.getElementById("document-tree-view");

    if (!vue_mount_point) {
        return;
    }

    const project_id = Number.parseInt(vue_mount_point.dataset.projectId, 10);
    const project_name = vue_mount_point.dataset.projectName;
    const user_is_admin = Boolean(vue_mount_point.dataset.userIsAdmin);
    const user_can_create_wiki = Boolean(vue_mount_point.dataset.userCanCreateWiki);
    const user_timezone = document.body.dataset.userTimezone;
    const date_time_format = document.body.dataset.dateTimeFormat;
    const user_id = Number.parseInt(document.body.dataset.userId, 10);
    const max_files_dragndrop = Number.parseInt(vue_mount_point.dataset.maxFilesDragndrop, 10);
    const max_size_upload = Number.parseInt(vue_mount_point.dataset.maxSizeUpload, 10);
    const embedded_are_allowed = Boolean(vue_mount_point.dataset.embeddedAreAllowed);
    const is_deletion_allowed = Boolean(vue_mount_point.dataset.userCanDeleteItem);
    const is_item_status_metadata_used = Boolean(vue_mount_point.dataset.isItemStatusMetadataUsed);
    const is_obsolescence_date_metadata_used = Boolean(
        vue_mount_point.dataset.isObsolescenceDateMetadataUsed
    );
    const csrf_token_name = vue_mount_point.dataset.csrfTokenName;
    const csrf_token = vue_mount_point.dataset.csrfToken;

    moment.tz(user_timezone);
    moment.locale(user_locale);

    const AppComponent = Vue.extend(App);
    const store = createStore(user_id, project_id);
    const router = createRouter(store, project_name);

    new AppComponent({
        store,
        router,
        propsData: {
            user_id,
            project_id,
            user_is_admin,
            user_can_create_wiki,
            date_time_format,
            max_files_dragndrop,
            max_size_upload,
            embedded_are_allowed,
            is_deletion_allowed,
            is_item_status_metadata_used,
            is_obsolescence_date_metadata_used,
            csrf_token_name,
            csrf_token,
        },
    }).$mount(vue_mount_point);
});

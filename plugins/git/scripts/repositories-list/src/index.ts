/*
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import Vue from "vue";
import TimeAgo from "javascript-time-ago";
import time_ago_english from "javascript-time-ago/locale/en";
import time_ago_french from "javascript-time-ago/locale/fr";
import VueDOMPurifyHTML from "vue-dompurify-html";
import { getDatasetItemOrThrow } from "@tuleap/dom";
import App from "./components/App.vue";
import "../themes/main.scss";
import { setBreadcrumbSettings } from "./breadcrumb-presenter";
import { build as buildRepositoryListPresenter } from "./repository-list-presenter";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue2-gettext-init";
import { createStore } from "./store";
import { ERROR_TYPE_NO_ERROR, PROJECT_KEY } from "./constants";
import type { State } from "./type";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("git-repository-list");
    if (!vue_mount_point) {
        return;
    }

    const locale = getDatasetItemOrThrow(document.body, "userLocale");

    await initVueGettext(
        Vue,
        (locale: string) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    Vue.config.language = locale;
    Vue.use(VueDOMPurifyHTML);

    TimeAgo.locale(time_ago_english);
    TimeAgo.locale(time_ago_french);

    let display_mode = "";
    if (vue_mount_point.dataset.displayMode) {
        display_mode = vue_mount_point.dataset.displayMode;
    }

    setBreadcrumbSettings(
        getDatasetItemOrThrow(vue_mount_point, "repositoriesAdministrationUrl"),
        getDatasetItemOrThrow(vue_mount_point, "repositoryListUrl"),
        getDatasetItemOrThrow(vue_mount_point, "repositoriesForkUrl"),
        getDatasetItemOrThrow(vue_mount_point, "projectPublicName"),
        getDatasetItemOrThrow(vue_mount_point, "projectUrl"),
        JSON.parse(getDatasetItemOrThrow(vue_mount_point, "privacy")),
        JSON.parse(getDatasetItemOrThrow(vue_mount_point, "projectFlags")),
        vue_mount_point.dataset.projectIcon || "",
    );

    const repositories_owners = getDatasetItemOrThrow(vue_mount_point, "repositoriesOwners");

    buildRepositoryListPresenter(
        Number.parseInt(getDatasetItemOrThrow(document.body, "userId"), 10),
        Number.parseInt(getDatasetItemOrThrow(vue_mount_point, "projectId"), 10),
        Boolean(vue_mount_point.dataset.isAdmin),
        locale,
        JSON.parse(repositories_owners),
        JSON.parse(getDatasetItemOrThrow(vue_mount_point, "externalPlugins")),
        Boolean(getDatasetItemOrThrow(vue_mount_point, "isOldPullRequestDashboardViewEnabled")),
    );

    const state: State = {
        repositories_for_owner: JSON.parse(repositories_owners),
        filter: "",
        selected_owner_id: PROJECT_KEY,
        error_message_type: ERROR_TYPE_NO_ERROR,
        success_message: "",
        is_loading_initial: true,
        is_loading_next: true,
        add_repository_modal: null,
        display_mode: display_mode,
        is_first_load_done: false,
        services_name_used: JSON.parse(
            getDatasetItemOrThrow(vue_mount_point, "externalServicesNameUsed"),
        ),
        add_gitlab_repository_modal: null,
        unlink_gitlab_repository_modal: null,
        unlink_gitlab_repository: null,
    };

    const AppComponent = Vue.extend(App);

    new AppComponent({
        store: createStore(state),
    }).$mount(vue_mount_point);
});

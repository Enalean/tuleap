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

import { createApp } from "vue";
import { createGettext } from "vue3-gettext";
import TimeAgo from "javascript-time-ago";
import time_ago_english from "javascript-time-ago/locale/en";
import time_ago_french from "javascript-time-ago/locale/fr";
import { getAttributeOrThrow } from "@tuleap/dom";
import App from "./components/App.vue";
import "../themes/main.scss";
import { setBreadcrumbSettings } from "./breadcrumb-presenter";
import { build as buildRepositoryListPresenter } from "./repository-list-presenter";
import { getPOFileFromLocaleWithoutExtension, initVueGettext } from "@tuleap/vue3-gettext-init";
import { createInitializedStore } from "./store";
import { ERROR_TYPE_NO_ERROR, PROJECT_KEY } from "./constants";
import type { RepositoryOwner, State } from "./type";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("git-repository-list");
    if (!vue_mount_point) {
        return;
    }

    const locale = getAttributeOrThrow(document.body, "data-user-Locale");

    const gettext_plugin = await initVueGettext(
        createGettext,
        (locale: string) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    TimeAgo.locale(time_ago_english);
    TimeAgo.locale(time_ago_french);

    let display_mode = "";
    if (vue_mount_point.dataset.displayMode) {
        display_mode = vue_mount_point.dataset.displayMode;
    }

    setBreadcrumbSettings(
        getAttributeOrThrow(vue_mount_point, "data-repositories-administration-url"),
        getAttributeOrThrow(vue_mount_point, "data-repository-list-url"),
        getAttributeOrThrow(vue_mount_point, "data-repositories-fork-url"),
        getAttributeOrThrow(vue_mount_point, "data-project-public-name"),
        getAttributeOrThrow(vue_mount_point, "data-project-url"),
        JSON.parse(getAttributeOrThrow(vue_mount_point, "data-privacy")),
        JSON.parse(getAttributeOrThrow(vue_mount_point, "data-project-flags")),
        vue_mount_point.dataset.projectIcon || "",
    );

    const repositories_owners = getAttributeOrThrow(vue_mount_point, "data-repositories-owners");

    buildRepositoryListPresenter(
        Number.parseInt(getAttributeOrThrow(document.body, "data-user-id"), 10),
        Number.parseInt(getAttributeOrThrow(vue_mount_point, "data-project-id"), 10),
        Boolean(vue_mount_point.dataset.isAdmin),
        locale,
        JSON.parse(repositories_owners).sort(function (
            user_a: RepositoryOwner,
            user_b: RepositoryOwner,
        ) {
            return user_a.display_name.localeCompare(user_b.display_name);
        }),
        JSON.parse(getAttributeOrThrow(vue_mount_point, "data-external-plugins")),
    );

    const state: State = {
        repositories_for_owner: [
            {
                id: PROJECT_KEY,
                repositories: [],
            },
        ],
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
            getAttributeOrThrow(vue_mount_point, "data-external-services-name-used"),
        ),
        add_gitlab_repository_modal: null,
        unlink_gitlab_repository_modal: null,
        unlink_gitlab_repository: null,
    };

    createApp(App).use(createInitializedStore(state)).use(gettext_plugin).mount(vue_mount_point);
});

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
import App from "./components/App.vue";
import "../themes/main.scss";
import { setBreadcrumbSettings } from "./breadcrumb-presenter";
import { build as buildRepositoryListPresenter } from "./repository-list-presenter";
import {
    getPOFileFromLocaleWithoutExtension,
    initVueGettextFromPoGettextPlugin,
} from "@tuleap/vue2-gettext-init";
import { createStore } from "./store";
import { ERROR_TYPE_NO_ERROR, PROJECT_KEY } from "./constants";
import type { State, RepositoriesForOwner } from "./type";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("git-repository-list");
    if (!vue_mount_point) {
        return;
    }

    const locale = document.body.dataset.userLocale;
    if (locale === undefined) {
        throw new Error("Unable to load user locale");
    }

    await initVueGettextFromPoGettextPlugin(
        Vue,
        (locale: string) => import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );

    Vue.config.language = locale;
    Vue.use(VueDOMPurifyHTML);

    TimeAgo.locale(time_ago_english);
    TimeAgo.locale(time_ago_french);

    const AppComponent = Vue.extend(App);

    if (!vue_mount_point.dataset.repositoriesAdministrationUrl) {
        throw new Error("Missing repositoriesAdministrationUrl dataset");
    }
    const repositoriesAdministrationUrl = vue_mount_point.dataset.repositoriesAdministrationUrl;

    if (!vue_mount_point.dataset.repositoryListUrl) {
        throw new Error("Missing repositoryListUrl dataset");
    }
    const repositoryListUrl = vue_mount_point.dataset.repositoryListUrl;

    if (!vue_mount_point.dataset.repositoriesForkUrl) {
        throw new Error("Missing repositoriesForkUrl dataset");
    }
    const repositoriesForkUrl = vue_mount_point.dataset.repositoriesForkUrl;

    if (!vue_mount_point.dataset.projectId) {
        throw new Error("Missing projectId dataset");
    }
    const projectId = vue_mount_point.dataset.projectId;

    const isAdmin = Boolean(vue_mount_point.dataset.isAdmin);

    if (!vue_mount_point.dataset.repositoriesOwners) {
        throw new Error("Missing repositoriesOwners dataset");
    }
    const repositoriesOwners = vue_mount_point.dataset.repositoriesOwners;

    let displayMode = "";
    if (vue_mount_point.dataset.displayMode) {
        displayMode = vue_mount_point.dataset.displayMode;
    }

    if (!vue_mount_point.dataset.externalPlugins) {
        throw new Error("Missing externalPlugins dataset");
    }
    const externalPlugins = vue_mount_point.dataset.externalPlugins;

    if (!vue_mount_point.dataset.externalServicesNameUsed) {
        throw new Error("Missing externalServicesNameUsed dataset");
    }
    const externalServicesNameUsed = vue_mount_point.dataset.externalServicesNameUsed;

    if (!vue_mount_point.dataset.projectPublicName) {
        throw new Error("Missing projectPublicName dataset");
    }
    const projectPublicName = vue_mount_point.dataset.projectPublicName;

    if (!vue_mount_point.dataset.projectUrl) {
        throw new Error("Missing projectUrl dataset");
    }
    const projectUrl = vue_mount_point.dataset.projectUrl;

    if (!vue_mount_point.dataset.privacy) {
        throw new Error("Missing privacy dataset");
    }
    const privacy = vue_mount_point.dataset.privacy;

    if (!vue_mount_point.dataset.projectFlags) {
        throw new Error("Missing projectFlags dataset");
    }
    const projectFlags = vue_mount_point.dataset.projectFlags;

    if (!document.body.dataset.userId) {
        throw new Error("Missing userId dataset");
    }
    const userId = document.body.dataset.userId;

    setBreadcrumbSettings(
        repositoriesAdministrationUrl,
        repositoryListUrl,
        repositoriesForkUrl,
        projectPublicName,
        projectUrl,
        JSON.parse(privacy),
        JSON.parse(projectFlags),
        vue_mount_point.dataset.projectIcon || "",
    );
    buildRepositoryListPresenter(
        parseInt(userId, 10),
        parseInt(projectId, 10),
        isAdmin,
        locale,
        JSON.parse(repositoriesOwners),
        JSON.parse(externalPlugins),
    );

    const repositories_for_owner: RepositoriesForOwner = JSON.parse(repositoriesOwners);
    const state: State = {
        repositories_for_owner,
        filter: "",
        selected_owner_id: PROJECT_KEY,
        error_message_type: ERROR_TYPE_NO_ERROR,
        success_message: "",
        is_loading_initial: true,
        is_loading_next: true,
        add_repository_modal: null,
        display_mode: displayMode,
        is_first_load_done: false,
        services_name_used: JSON.parse(externalServicesNameUsed),
        add_gitlab_repository_modal: null,
        unlink_gitlab_repository_modal: null,
        unlink_gitlab_repository: null,
    };
    new AppComponent({
        store: createStore(state),
    }).$mount(vue_mount_point);
});

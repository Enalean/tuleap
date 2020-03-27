/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
import TimeAgo from "javascript-time-ago";
import time_ago_english from "javascript-time-ago/locale/en";
import time_ago_french from "javascript-time-ago/locale/fr";

import french_translations from "../po/fr.po";
import App from "./components/App.vue";
import { setUrls } from "./breadcrumb-presenter.js";
import { build as buildRepositoryListPresenter } from "./repository-list-presenter.js";

document.addEventListener("DOMContentLoaded", () => {
    Vue.use(GetTextPlugin, {
        translations: {
            fr: french_translations.messages,
        },
        silent: true,
    });

    const locale = document.body.dataset.userLocale;
    Vue.config.language = locale;
    TimeAgo.locale(time_ago_english);
    TimeAgo.locale(time_ago_french);

    const vue_mount_point = document.getElementById("git-repository-list");

    if (vue_mount_point) {
        const AppComponent = Vue.extend(App);

        const {
            repositoriesAdministrationUrl,
            repositoryListUrl,
            repositoriesForkUrl,
            projectId,
            isAdmin,
            repositoriesOwners,
            displayMode,
        } = vue_mount_point.dataset;

        setUrls(repositoriesAdministrationUrl, repositoryListUrl, repositoriesForkUrl);
        buildRepositoryListPresenter(
            document.body.dataset.userId,
            projectId,
            isAdmin,
            locale,
            JSON.parse(repositoriesOwners)
        );

        new AppComponent({
            propsData: {
                displayMode,
            },
        }).$mount(vue_mount_point);
    }
});

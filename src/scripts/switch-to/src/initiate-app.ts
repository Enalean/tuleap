/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { Component } from "vue";
import { createApp, readonly } from "vue";
import { createPinia } from "pinia";
import { createGettext } from "vue3-gettext";
import { initVueGettext, getPOFileFromLocaleWithoutExtension } from "@tuleap/vue3-gettext-init";
import type { FullTextState, State } from "./stores/type";
import { useRootStore } from "./stores/root";
import { useFullTextStore } from "./stores/fulltext";
import { getProjectsFromDataset } from "./helpers/get-projects-from-dataset";
import {
    ARE_RESTRICTED_USERS_ALLOWED,
    IS_SEARCH_AVAILABLE,
    IS_TROVE_CAT_ENABLED,
    SEARCH_FORM,
} from "./injection-keys";
import { uri } from "@tuleap/fetch-result";

export async function init(vue_mount_point: HTMLElement, component: Component): Promise<void> {
    const gettext = await initVueGettext(createGettext, (locale: string) => {
        return import(`../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`);
    });

    const pinia = createPinia();
    const root_state: State = {
        projects: getProjectsFromDataset(vue_mount_point.dataset.projects, gettext.$gettext),
        filter_value: "",
        user_id: parseInt(document.body.dataset.userId || "0", 10),
        is_loading_history: true,
        is_history_loaded: false,
        is_history_in_error: false,
        history: { entries: [] },
    };

    const app = createApp(component);
    app.use(pinia);
    app.provide(IS_SEARCH_AVAILABLE, Boolean(vue_mount_point.dataset.isSearchAvailable));
    app.provide(IS_TROVE_CAT_ENABLED, Boolean(vue_mount_point.dataset.isTroveCatEnabled));
    app.provide(
        ARE_RESTRICTED_USERS_ALLOWED,
        Boolean(vue_mount_point.dataset.areRestrictedUsersAllowed),
    );
    app.provide(
        SEARCH_FORM,
        readonly(
            typeof vue_mount_point.dataset.searchForm !== "undefined"
                ? JSON.parse(vue_mount_point.dataset.searchForm)
                : { type_of_search: "soft", hidden_fields: [] },
        ),
    );

    const store = useRootStore();
    store.$patch(root_state);

    const fulltext_state: FullTextState = {
        fulltext_search_url: uri`/api/v1/search`,
        fulltext_search_results: {},
        fulltext_search_is_error: false,
        fulltext_search_is_loading: false,
        fulltext_search_is_available: true,
        fulltext_search_has_more_results: false,
    };
    const fulltext_store = useFullTextStore();
    fulltext_store.$patch(fulltext_state);

    app.use(gettext);
    app.mount(vue_mount_point);
}

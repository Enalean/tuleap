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
import VueRouter from "vue-router";
import RootFolder from "../components/Folder/RootFolder.vue";
import ChildFolder from "../components/Folder/ChildFolder.vue";
import DisplayEmbedded from "../components/EmbeddedDisplay/DisplayEmbedded.vue";
import DisplayHistory from "../components/History/DisplayHistory.vue";
import DisplayVersions from "../components/Versions/DisplayVersions.vue";
import SearchContainer from "../components/AdvancedSearch/SearchContainer.vue";
import { abortCurrentUploads } from "../helpers/abort-current-uploads";
import { getSearchPropsFromRoute } from "./get-search-props-from-route";

Vue.use(VueRouter);

export function createRouter(store, project_name) {
    const router = new VueRouter({
        mode: "history",
        base: "/plugins/document/" + project_name + "/",
        routes: [
            {
                path: "/",
                name: "root_folder",
                component: RootFolder,
            },
            {
                path: "/folder/:item_id",
                name: "folder",
                component: ChildFolder,
            },
            {
                path: "/folder/:folder_id/:item_id/:version_id",
                name: "item_version",
                component: DisplayEmbedded,
                props: (route) => ({
                    item_id: Number(route.params.item_id),
                    version_id: Number(route.params.version_id),
                }),
            },
            {
                path: "/folder/:folder_id/:item_id",
                name: "item",
                component: DisplayEmbedded,
                props: (route) => ({ item_id: Number(route.params.item_id) }),
            },
            {
                path: "/preview/:preview_item_id",
                name: "preview",
                component: ChildFolder,
            },
            {
                path: "/history/:item_id",
                name: "history",
                component: DisplayHistory,
            },
            {
                path: "/versions/:item_id",
                name: "versions",
                component: DisplayVersions,
            },
            {
                path: "/search/:folder_id?",
                name: "search",
                component: SearchContainer,
                props: (route) =>
                    getSearchPropsFromRoute(
                        route,
                        store.state.configuration.root_id,
                        store.state.configuration.criteria
                    ),
            },
        ],
    });

    router.beforeEach((to, from, next) => {
        if (!store.getters.is_uploading || abortCurrentUploads(router.app.$gettext, store)) {
            store.commit("error/resetErrors");
            store.commit("emptyFilesUploadsList");
            store.commit("resetFoldedLists");
            next();
        } else {
            next(false);
        }
    });

    return router;
}

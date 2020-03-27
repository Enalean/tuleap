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
import DisplayEmbedded from "../components/Folder/ItemDisplay/DisplayEmbedded.vue";
import { abortCurrentUploads } from "../helpers/abort-current-uploads.js";

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
                path: "/folder/:folder_id/:item_id",
                name: "item",
                component: DisplayEmbedded,
            },
            {
                path: "/preview/:preview_item_id",
                name: "preview",
                component: ChildFolder,
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

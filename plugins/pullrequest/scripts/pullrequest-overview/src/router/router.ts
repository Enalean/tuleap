/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import type { Router } from "vue-router";
import { createRouter, createWebHashHistory } from "vue-router";
import OverviewPane from "../components/OverviewPane.vue";
import { VIEW_OVERVIEW_NAME } from "../constants";
import { buildVueOverviewBaseUrl } from "./base-url-builders";

export function createOverviewRouter(base_url: URL): Router {
    return createRouter({
        history: createWebHashHistory(buildVueOverviewBaseUrl(base_url).toString()),
        routes: [
            {
                path: "/pull-requests/:id/overview",
                name: VIEW_OVERVIEW_NAME,
                component: OverviewPane,
            },
        ],
    });
}

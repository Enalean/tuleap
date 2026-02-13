/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
import { createRouter, createWebHistory } from "vue-router";
import PaletteContainer from "../components/Sidebar/Palette/PaletteContainer.vue";
import FieldEdition from "../components/Sidebar/FieldEdition/FieldEdition.vue";
import NotFound from "../components/NotFound.vue";

export function getRouter(base_uri: string): Router {
    return createRouter({
        history: createWebHistory(base_uri),
        routes: [
            {
                path: "/",
                name: "fields-usage",
                components: {
                    sidebar: PaletteContainer,
                },
            },
            {
                path: "/:field_id(\\d+)",
                name: "field-edition",
                components: {
                    sidebar: FieldEdition,
                },
                props: {
                    sidebar: (route) => ({ field_id: Number(route.params.field_id) }),
                },
            },
            {
                path: "/:catchAll(.*)",
                name: "not-found",
                components: {
                    sidebar: PaletteContainer,
                    error: NotFound,
                },
            },
        ],
    });
}

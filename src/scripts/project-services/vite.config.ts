/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { vite } from "@tuleap/build-system-configurator";
import vue from "@vitejs/plugin-vue2";
import * as path from "node:path";
import POGettextPlugin from "@tuleap/po-gettext-plugin";

export default vite.defineAppConfig(
    {
        plugin_name: "core",
        sub_app_name: path.basename(__dirname),
    },
    {
        plugins: [vue(), POGettextPlugin.vite()],
        build: {
            rollupOptions: {
                input: {
                    "project-admin-services": path.resolve(__dirname, "src/index-project-admin.js"),
                    "site-admin-services": path.resolve(__dirname, "src/index-site-admin.js"),
                },
            },
        },
        resolve: {
            dedupe: ["vue"],
        },
    },
);

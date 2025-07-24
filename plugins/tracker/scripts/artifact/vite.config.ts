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
import * as path from "node:path";
import { viteExternalsPlugin } from "vite-plugin-externals";
import POGettextPlugin from "@tuleap/po-gettext-plugin";

export default vite.defineAppConfig(
    {
        plugin_name: "trackers",
        sub_app_name: path.basename(__dirname),
    },
    {
        plugins: [
            POGettextPlugin.vite(),
            viteExternalsPlugin({
                ckeditor4: "CKEDITOR",
                codendi: "codendi",
                lytebox: "LyteBox",
                tuleap: "tuleap",
                jquery: "jQuery",
            }),
        ],
        build: {
            rollupOptions: {
                input: {
                    "create-view": path.resolve(__dirname, "src/creation/create-view.ts"),
                    "edit-view": path.resolve(__dirname, "src/edition/edit-view.ts"),
                    "link-tab-view": path.resolve(__dirname, "src/link-tab/link-tab-view.ts"),
                    "artifact-links-field": path.resolve(
                        __dirname,
                        "src/fields/artifact-links-field.ts",
                    ),
                    "cross-references-fields": path.resolve(
                        __dirname,
                        "src/fields/cross-references-fields.ts",
                    ),
                    "legacy-modal-v1": path.resolve(
                        __dirname,
                        "src/legacy-modal-v1/legacy-modal-v1.ts",
                    ),
                    "mass-change": path.resolve(__dirname, "src/mass-change/mass-change-view.ts"),
                },
            },
        },
    },
);

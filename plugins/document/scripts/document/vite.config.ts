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
import POGettextPlugin from "@tuleap/po-gettext-plugin";
import vue from "@vitejs/plugin-vue";
import { viteExternalsPlugin } from "vite-plugin-externals";

export default vite.defineAppConfig(
    {
        plugin_name: path.basename(path.resolve(__dirname, "../..")),
        sub_app_name: path.basename(__dirname),
    },
    {
        plugins: [
            POGettextPlugin.vite(),
            viteExternalsPlugin({ tlp: "tlp" }),
            vue({
                template: {
                    compilerOptions: {
                        isCustomElement: (tag: string) => tag === "tlp-relative-date",
                    },
                },
            }),
        ],
        build: {
            rollupOptions: {
                input: {
                    document: path.resolve(__dirname, "src/index.ts"),
                },
            },
        },
        resolve: {
            dedupe: ["vue"],
        },
        assetsInclude: "**/*.{docx,pptx,xlsx}",
    },
);

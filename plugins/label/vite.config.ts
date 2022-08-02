/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
import * as path from "path";
import vue from "@vitejs/plugin-vue2";
import { viteExternalsPlugin } from "vite-plugin-externals";
import POGettextPlugin from "@tuleap/po-gettext-plugin";

export default vite.defineAppConfig("label", {
    plugins: [vue(), POGettextPlugin.vite(), viteExternalsPlugin({ tlp: "tlp" })],
    build: {
        rollupOptions: {
            input: {
                "configure-widget": path.resolve(__dirname, "scripts/configure-widget/index.js"),
                "widget-project-labeled-items": path.resolve(
                    __dirname,
                    "scripts/project-labeled-items/src/index.js"
                ),
            },
        },
    },
});

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

import path from "node:path";
import { fileURLToPath } from "node:url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

import { webpack_configurator } from "@tuleap/build-system-configurator";

const assets_dir_path = path.resolve(__dirname, "./frontend-assets");
const assets_public_path = "/assets/document/";

const entry_points = {
    "admin-search-view": "./scripts/admin-search-view/index.ts",
};

export default [
    {
        entry: entry_points,
        context: path.resolve(__dirname),
        output: webpack_configurator.configureOutput(assets_dir_path, assets_public_path),
        externals: {
            tlp: "tlp",
        },
        resolve: {
            extensions: [".ts", ".js", ".vue"],
            alias: {
                vue: path.resolve(__dirname, "node_modules", "vue"),
            },
        },
        module: {
            rules: webpack_configurator.configureTypescriptRules(),
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            webpack_configurator.getMomentLocalePlugin(),
        ],
    },
];

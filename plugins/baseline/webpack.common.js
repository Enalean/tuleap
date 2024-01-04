/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import { webpack_configurator } from "@tuleap/build-system-configurator";
import POGettextPlugin from "@tuleap/po-gettext-plugin";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default [
    {
        entry: {
            baseline: "./scripts/baseline/src/index.js",
            "baseline-style": "./themes/baseline.scss",
        },
        context: __dirname,
        output: webpack_configurator.configureOutput(
            path.resolve(__dirname, "./frontend-assets"),
            "/assets/baseline/",
        ),
        resolve: {
            extensions: [".ts", ".js", ".vue"],
            alias: {
                vue: path.resolve(__dirname, "node_modules", "vue"),
            },
        },
        externals: {
            tlp: "tlp",
        },
        module: {
            rules: [
                // Allow omitting file extension when importing from a .js file. This matches .ts behaviour
                { test: /\.js$/, resolve: { fullySpecified: false } },
                ...webpack_configurator.configureTypescriptRules(),
                webpack_configurator.rule_vue_loader,
                webpack_configurator.rule_scss_loader,
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            POGettextPlugin.webpack(),
            webpack_configurator.getVueLoaderPlugin(),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
    },
];

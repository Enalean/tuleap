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

import path from "path";
import { fileURLToPath } from "url";
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
import { webpack_configurator } from "@tuleap/build-system-configurator";

const manifest_plugin = webpack_configurator.getManifestPlugin();
const context = path.resolve(__dirname);
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "./frontend-assets"),
    "/assets/git/"
);

const webpack_config_for_vue = {
    entry: {
        repository: "./scripts/repository/src/index.ts",
        "line-highlight": "./scripts/repository/file/line-highlight.ts",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader,
        ],
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getTypescriptCheckerPlugin(true),
        webpack_configurator.getVueLoaderPlugin(),
    ],
    resolve: {
        extensions: [".ts", ".js", ".vue"],
        alias: {
            vue: path.resolve(__dirname, "node_modules", "vue"),
        },
    },
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias,
    },
};

const webpack_config_for_vanilla = {
    entry: {
        "repo-admin-notifications": "./scripts/admin-notifications.js",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
        jquery: "jQuery",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
        ],
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getTypescriptCheckerPlugin(false),
    ],
    resolve: {
        extensions: [".ts", ".js"],
    },
};

const entry_points = {
    "bp-style": "./themes/BurningParrot/git.scss",
};

const webpack_config_for_themes = {
    entry: entry_points,
    context,
    output,
    module: {
        rules: [webpack_configurator.rule_scss_loader, webpack_configurator.rule_css_assets],
    },
    plugins: [manifest_plugin, ...webpack_configurator.getCSSExtractionPlugins()],
};

export default [
    webpack_config_for_vue,
    webpack_config_for_vanilla,
    webpack_config_for_themes,
];

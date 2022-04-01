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

const path = require("path");
const webpack_configurator = require("../../tools/utils/scripts/webpack-configurator.js");

const entry = {
    "new-thread": "./scripts/new-thread.ts",
    thread: "./scripts/thread.ts",
    style: "./themes/css/style.scss",
    "forumml-style": "./themes/BurningParrot/css/forumml.scss",
};

module.exports = [
    {
        entry,
        context: path.resolve(__dirname),
        output: webpack_configurator.configureOutput(
            path.resolve(__dirname, "./frontend-assets/"),
            "/assets/forumml/"
        ),
        externals: {
            tlp: "tlp",
        },
        resolve: {
            extensions: [".ts", ".js", ".vue"],
        },
        module: {
            rules: [
                ...webpack_configurator.configureTypescriptRules(),
                webpack_configurator.rule_easygettext_loader,
                webpack_configurator.rule_vue_loader,
                webpack_configurator.rule_scss_loader,
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getVueLoaderPlugin(),
            ...webpack_configurator.getCSSExtractionPlugins(),
            ...webpack_configurator.getLegacyConcatenatedScriptsPlugins({
                "forumml.js": ["./scripts/forumml.js", "./scripts/cc_attach.js"],
            }),
            webpack_configurator.getManifestPlugin(),
        ],
        resolveLoader: {
            alias: webpack_configurator.easygettext_loader_alias,
        },
    },
];

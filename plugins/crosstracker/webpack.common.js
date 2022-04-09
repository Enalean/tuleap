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
const { webpack_configurator } = require("@tuleap/build-system-configurator");

let entry_points = {
    "cross-tracker": "./scripts/cross-tracker/src/index.ts",
    "cross-tracker-style": "./themes/cross-tracker.scss",
};

module.exports = [
    {
        entry: entry_points,
        context: __dirname,
        output: webpack_configurator.configureOutput(path.resolve(__dirname, "./frontend-assets/")),
        externals: {
            tlp: "tlp",
        },
        module: {
            rules: [
                webpack_configurator.rule_css_assets,
                webpack_configurator.rule_scss_loader,
                webpack_configurator.rule_easygettext_loader,
                webpack_configurator.rule_vue_loader,
                ...webpack_configurator.configureTypescriptRules(),
            ],
        },
        resolve: {
            extensions: [".ts", ".js", ".vue"],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            webpack_configurator.getVueLoaderPlugin(),
            webpack_configurator.getMomentLocalePlugin(),
            webpack_configurator.getTypescriptCheckerPlugin(true),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
        resolveLoader: {
            alias: webpack_configurator.easygettext_loader_alias,
        },
    },
];

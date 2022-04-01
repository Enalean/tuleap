/*
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "./frontend-assets"),
    "/assets/roadmap/"
);

module.exports = [
    {
        entry: {
            "widget-script": "./scripts/roadmap-widget/src/index.ts",
            "widget-style": "./themes/widget-roadmap.scss",
            "configure-roadmap-widget-script": "./scripts/configure-widget/src/index.ts",
            "configure-roadmap-widget-style": "./themes/widget-configuration.scss",
        },
        context,
        output,
        resolve: {
            extensions: [".js", ".ts", ".vue"],
        },
        externals: {
            tlp: "tlp",
        },
        module: {
            rules: [
                ...webpack_configurator.configureTypescriptRules(
                    webpack_configurator.babel_options_chrome_firefox
                ),
                webpack_configurator.rule_easygettext_loader,
                webpack_configurator.rule_vue_loader,
                webpack_configurator.rule_scss_loader,
                webpack_configurator.rule_css_assets,
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            webpack_configurator.getVueLoaderPlugin(),
            webpack_configurator.getTypescriptCheckerPlugin(true),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
        resolveLoader: {
            alias: webpack_configurator.easygettext_loader_alias,
        },
    },
];

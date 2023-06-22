/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

const path = require("node:path");
const { webpack_configurator } = require("@tuleap/build-system-configurator");

const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "./frontend-assets/"),
    "/assets/agiledashboard/planning-v2/"
);

const webpack_config_for_planning_v2 = {
    entry: {
        "planning-v2": "./src/app/app.js",
        "planning-style": "./themes/main.scss",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
        jquery: "jQuery",
        ckeditor4: "CKEDITOR",
    },
    resolve: {
        alias: {
            // angular alias for the artifact modal (otherwise it is included twice)
            angular$: path.resolve(__dirname, "./node_modules/angular"),
            "angular-sanitize$": path.resolve(__dirname, "./node_modules/angular-sanitize"),
        },
        extensions: [".ts", ".js"],
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_ng_cache_loader,
            webpack_configurator.rule_angular_gettext_loader,
            webpack_configurator.rule_scss_loader,
            webpack_configurator.rule_css_assets,
        ],
    },
    plugins: [
        webpack_configurator.getCleanWebpackPlugin(),
        webpack_configurator.getManifestPlugin(),
        webpack_configurator.getMomentLocalePlugin(),
        ...webpack_configurator.getCSSExtractionPlugins(),
    ],
};

module.exports = [webpack_config_for_planning_v2];

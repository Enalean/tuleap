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

const path = require("path");
const webpack_configurator = require("../../../tools/utils/scripts/webpack-configurator.js");
const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "../../../src/www/assets/agiledashboard/js/"),
    "/assets/agiledashboard/js/"
);
const manifest_plugin = webpack_configurator.getManifestPlugin();

const webpack_config_for_typescript_scripts = {
    entry: {
        "artifact-additional-action": "./artifact-additional-action/src/index.ts",
        administration: "./administration/administration.ts"
    },
    context,
    output,
    externals: {
        tlp: "tlp"
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files
        ]
    },
    plugins: [manifest_plugin, webpack_configurator.getTypescriptCheckerPlugin(false)],
    resolve: {
        extensions: [".ts", ".js"]
    }
};

const webpack_config_for_charts = {
    entry: {
        "burnup-chart": "./burnup-chart/src/burnup-chart.js"
    },
    context,
    output,
    externals: {
        tuleap: "tuleap",
        jquery: "jQuery"
    },
    resolve: {
        alias: {
            "charts-builders": path.resolve(__dirname, "../../../src/www/scripts/charts-builders/"),
            "d3-array$": path.resolve(__dirname, "node_modules/d3-array"),
            "d3-scale$": path.resolve(__dirname, "node_modules/d3-scale"),
            "d3-axis$": path.resolve(__dirname, "node_modules/d3-axis")
        }
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files
        ]
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()]
};

const webpack_config_for_overview_and_vue = {
    entry: {
        "scrum-header": "./scrum-header.js",
        "permission-per-group": "./permissions-per-group/src/index.js",
        "planning-admin": "./planning-admin.js"
    },
    context,
    output,
    externals: {
        tlp: "tlp"
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader
        ]
    },
    plugins: [manifest_plugin, webpack_configurator.getVueLoaderPlugin()],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias
    }
};

module.exports = [
    webpack_config_for_charts,
    webpack_config_for_typescript_scripts,
    webpack_config_for_overview_and_vue
];

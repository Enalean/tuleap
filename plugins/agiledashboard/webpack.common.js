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
const webpack_configurator = require("../../tools/utils/scripts/webpack-configurator.js");

const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "./frontend-assets/"),
    "/assets/agiledashboard/"
);
const manifest_plugin = webpack_configurator.getManifestPlugin();

const entry_points = {
    "burnup-chart": "./themes/FlamingParrot/css/burnup-chart.scss",
    "style-fp": "./themes/FlamingParrot/css/style.scss",
    "planning-admin-colorpicker": "./themes/FlamingParrot/css/planning-admin-colorpicker.scss",
    "administration-style": "./themes/BurningParrot/css/administration.scss",
    "scrum-style": "./themes/BurningParrot/css/scrum.scss",
    "kanban-style": "./themes/BurningParrot/css/kanban.scss",
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

const webpack_config_for_typescript = {
    entry: {
        "artifact-additional-action": "./scripts/artifact-additional-action/src/index.ts",
        administration: "./scripts/administration/administration.ts",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_po_files,
        ],
    },
    plugins: [manifest_plugin],
    resolve: {
        extensions: [".ts", ".js"],
    },
};

const webpack_config_for_charts = {
    entry: {
        "burnup-chart": "./scripts/burnup-chart/src/burnup-chart.js",
    },
    context,
    output,
    externals: {
        tuleap: "tuleap",
    },
    resolve: {
        alias: {
            "charts-builders": path.resolve(__dirname, "../../src/scripts/charts-builders/"),
            "d3-array$": path.resolve(__dirname, "node_modules/d3-array"),
            "d3-scale$": path.resolve(__dirname, "node_modules/d3-scale"),
            "d3-axis$": path.resolve(__dirname, "node_modules/d3-axis"),
        },
    },
    module: {
        rules: [webpack_configurator.rule_po_files],
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()],
};

const webpack_config_for_javascript = {
    entry: {
        "home-burndowns": "./scripts/home.js",
        "scrum-header": "./scripts/scrum-header.js",
        "permission-per-group": "./scripts/permissions-per-group/src/index.js",
        "planning-admin": "./scripts/planning-admin.js",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
        codendi: "codendi",
        tuleap: "tuleap",
        jquery: "jQuery",
    },
    module: {
        rules: [webpack_configurator.rule_easygettext_loader, webpack_configurator.rule_vue_loader],
    },
    plugins: [manifest_plugin, webpack_configurator.getVueLoaderPlugin()],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias,
    },
};

const webpack_config_for_kanban = {
    entry: {
        kanban: "./scripts/kanban/src/app/app.js",
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
            // angular alias for angular-async (otherwise it is included twice)
            // and for the artifact modal
            angular$: path.resolve(__dirname, "./scripts/kanban/node_modules/angular"),
            "angular-sanitize$": path.resolve(
                __dirname,
                "./scripts/kanban/node_modules/angular-sanitize"
            ),
            // cumulative-flow-chart
            d3$: path.resolve(__dirname, "node_modules/d3"),
            lodash$: path.resolve(__dirname, "./scripts/kanban/node_modules/lodash"),
            moment$: path.resolve(__dirname, "node_modules/moment"),
        },
        extensions: [".ts", ".js"],
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_ng_cache_loader,
            webpack_configurator.rule_angular_gettext_loader,
        ],
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()],
};

const webpack_config_for_planning_v2 = {
    entry: {
        "planning-v2": "./scripts/planning-v2/src/app/app.js",
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
            angular$: path.resolve(__dirname, "./scripts/planning-v2/node_modules/angular"),
            "angular-sanitize$": path.resolve(
                __dirname,
                "./scripts/planning-v2/node_modules/angular-sanitize"
            ),
        },
        extensions: [".ts", ".js"],
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_ng_cache_loader,
            webpack_configurator.rule_angular_gettext_loader,
        ],
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()],
};

module.exports = [
    webpack_config_for_themes,
    webpack_config_for_charts,
    webpack_config_for_typescript,
    webpack_config_for_javascript,
    webpack_config_for_kanban,
    webpack_config_for_planning_v2,
];

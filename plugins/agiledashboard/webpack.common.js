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
    path.resolve(__dirname, "../../src/www/assets/agiledashboard/"),
    "/assets/agiledashboard/"
);
const manifest_plugin = webpack_configurator.getManifestPlugin();

const entry_points = {
    "burnup-chart": "./themes/FlamingParrot/css/burnup-chart.scss",
    "style-fp": "./themes/FlamingParrot/css/style.scss",
    "planning-admin-colorpicker": "./themes/FlamingParrot/css/planning-admin-colorpicker.scss",
};

const colors_burning_parrot = ["orange", "blue", "green", "red", "grey", "purple"];
const bp_entry_points = ["administration", "kanban", "scrum"];
for (const color of colors_burning_parrot) {
    for (const entry_point_name of bp_entry_points) {
        entry_points[
            entry_point_name + "-" + color
        ] = `./themes/BurningParrot/css/${entry_point_name}-${color}.scss`;
        entry_points[
            entry_point_name + "-" + color + "-condensed"
        ] = `./themes/BurningParrot/css/${entry_point_name}-${color}-condensed.scss`;
    }
}

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
            ...webpack_configurator.configureTypescriptRules(
                webpack_configurator.babel_options_ie11
            ),
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files,
        ],
    },
    plugins: [manifest_plugin, webpack_configurator.getTypescriptCheckerPlugin(false)],
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
        jquery: "jQuery",
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
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files,
        ],
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
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader,
        ],
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
        angular: "angular",
        jquery: "jQuery",
        ckeditor: "CKEDITOR",
    },
    resolve: {
        alias: webpack_configurator.extendAliases(webpack_configurator.tlp_fetch_alias, {
            "angular-tlp": path.resolve(__dirname, "../../src/themes/tlp/angular-tlp"),
            // cumulative-flow-chart
            d3$: path.resolve(__dirname, "node_modules/d3"),
            lodash$: path.resolve(__dirname, "./scripts/kanban/node_modules/lodash"),
            moment$: path.resolve(__dirname, "node_modules/moment"),
            // card-fields dependencies
            "angular-sanitize$": path.resolve(
                __dirname,
                "./scripts/kanban/node_modules/angular-sanitize"
            ),
            he$: path.resolve(__dirname, "node_modules/he"),
            striptags$: path.resolve(__dirname, "node_modules/striptags"),
            "escape-string-regexp$": path.resolve(__dirname, "node_modules/escape-string-regexp"),
        }),
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_ng_cache_loader,
            webpack_configurator.rule_vue_loader,
            webpack_configurator.rule_angular_mixed_vue_gettext,
            webpack_configurator.rule_angular_gettext_loader,
        ],
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getMomentLocalePlugin(),
        webpack_configurator.getVueLoaderPlugin(),
    ],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias,
    },
};

const webpack_config_for_angular = {
    entry: {
        angular: "./scripts/kanban/node_modules/angular",
    },
    context,
    output,
    plugins: [manifest_plugin],
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
        ckeditor: "CKEDITOR",
    },
    resolve: {
        alias: webpack_configurator.extendAliases(
            webpack_configurator.tlp_fetch_alias,
            webpack_configurator.angular_tlp_alias,
            {
                // card-fields dependencies
                angular$: path.resolve(__dirname, "./scripts/planning-v2/node_modules/angular"),
                "angular-sanitize$": path.resolve(
                    __dirname,
                    "./scripts/planning-v2/node_modules/angular-sanitize"
                ),
                moment$: path.resolve(__dirname, "node_modules/moment"),
                he$: path.resolve(__dirname, "node_modules/he"),
                striptags$: path.resolve(__dirname, "node_modules/striptags"),
                "escape-string-regexp$": path.resolve(
                    __dirname,
                    "node_modules/escape-string-regexp"
                ),
            }
        ),
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_ng_cache_loader,
            webpack_configurator.rule_vue_loader,
            webpack_configurator.rule_angular_mixed_vue_gettext,
            webpack_configurator.rule_angular_gettext_loader,
        ],
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getMomentLocalePlugin(),
        webpack_configurator.getVueLoaderPlugin(),
    ],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias,
    },
};

module.exports = [
    webpack_config_for_themes,
    webpack_config_for_charts,
    webpack_config_for_typescript,
    webpack_config_for_javascript,
    webpack_config_for_kanban,
    webpack_config_for_angular,
    webpack_config_for_planning_v2,
];

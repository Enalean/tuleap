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
const { webpack_configurator } = require("@tuleap/build-system-configurator");

const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "./frontend-assets/"),
    "/assets/agiledashboard/",
);
const manifest_plugin = webpack_configurator.getManifestPlugin();

const entry_points = {
    "burnup-chart": "./themes/FlamingParrot/css/burnup-chart.scss",
    "style-fp": "./themes/FlamingParrot/css/style.scss",
    "planning-admin-colorpicker": "./themes/FlamingParrot/css/planning-admin-colorpicker.scss",
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
            // deduplicate moment that is also used by chart-builder
            moment$: path.resolve(__dirname, "node_modules/moment"),
        },
    },
    module: {
        rules: [webpack_configurator.rule_po_files],
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()],
};

const webpack_config_for_javascript = {
    entry: {
        "planning-admin": "./scripts/planning-admin.js",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
        codendi: "codendi",
        tuleap: "tuleap",
    },
    plugins: [manifest_plugin],
};

module.exports = [
    webpack_config_for_themes,
    webpack_config_for_charts,
    webpack_config_for_javascript,
];

/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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
const webpack_configurator = require("../../../../tools/utils/scripts/webpack-configurator.js");

const assets_dir_path = path.resolve(__dirname, "../assets");
const assets_public_path = "assets/";
const manifest_plugin = webpack_configurator.getManifestPlugin();

const webpack_config_for_burndown_chart = {
    entry: {
        "burndown-chart": "./burndown-chart/src/burndown-chart.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    resolve: {
        alias: {
            "charts-builders": path.resolve(
                __dirname,
                "../../../../src/www/scripts/charts-builders/"
            ),
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

const path_to_badge = path.resolve(
    __dirname,
    "../../../../src/www/scripts/project/admin/permissions-per-group/"
);

const webpack_config_for_vue = {
    entry: {
        "tracker-report-expert-mode": "./report/index.js",
        "tracker-permissions-per-group": "./permissions-per-group/src/index.js",
        "tracker-workflow-transitions": "./workflow-transitions/src/index.js",
        MoveArtifactModal: "./artifact-action-buttons/src/index.js",
        TrackerAdminFields: "./TrackerAdminFields.js",
        "tracker-semantic-timeframe-option-selector": "./semantic-timeframe-option-selector"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path, assets_public_path),
    externals: {
        codendi: "codendi",
        jquery: "jQuery",
        tlp: "tlp"
    },
    resolve: {
        alias: webpack_configurator.extendAliases(webpack_configurator.tlp_fetch_alias, {
            "permission-badge": path_to_badge
        })
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

if (process.env.NODE_ENV === "watch" || process.env.NODE_ENV === "test") {
    webpack_config_for_burndown_chart.devtool = "cheap-module-eval-source-map";
    webpack_config_for_vue.devtool = "cheap-module-eval-source-map";
}

module.exports = [webpack_config_for_burndown_chart, webpack_config_for_vue];

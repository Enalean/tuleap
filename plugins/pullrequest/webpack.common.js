/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

const assets_output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "../../src/www/assets/pullrequest/")
);
const manifest_plugin = webpack_configurator.getManifestPlugin();

const webpack_config_main_angular_app = {
    entry: {
        "tuleap-pullrequest": "./scripts/src/app/app.js",
    },
    context: path.resolve(__dirname),
    output: assets_output,
    externals: {
        jquery: "jQuery",
        tlp: "tlp",
    },
    resolve: {
        alias: webpack_configurator.extendAliases(
            {
                ...webpack_configurator.angular_tlp_alias,
                // angular-tlp
                angular$: path.resolve(__dirname, "node_modules/angular"),
            },
            webpack_configurator.angular_tlp_alias
        ),
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_ng_cache_loader,
            webpack_configurator.rule_angular_gettext_loader,
        ],
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()],
};

const entry_points = {
    "create-pullrequest-button": "./scripts/create-pullrequest-button/src/index.js",
};

const colors = ["blue", "green", "grey", "orange", "purple", "red"];
for (const color of colors) {
    entry_points[`pull-requests-${color}`] = `./themes/pull-requests-${color}.scss`;
    entry_points[
        `pull-requests-${color}-condensed`
    ] = `./themes/pull-requests-${color}-condensed.scss`;
    entry_points[`repository-${color}`] = `./themes/repository-${color}.scss`;
    entry_points[`repository-${color}-condensed`] = `./themes/repository-${color}-condensed.scss`;
}

module.exports = [
    webpack_config_main_angular_app,
    {
        entry: entry_points,
        context: path.resolve(__dirname),
        output: assets_output,
        externals: {
            tlp: "tlp",
            jquery: "jQuery",
        },
        module: {
            rules: [
                webpack_configurator.rule_scss_loader,
                webpack_configurator.rule_css_assets,
                webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
                webpack_configurator.rule_easygettext_loader,
                webpack_configurator.rule_vue_loader,
            ],
        },
        plugins: [
            manifest_plugin,
            ...webpack_configurator.getCSSExtractionPlugins(),
            webpack_configurator.getVueLoaderPlugin(),
        ],
        resolveLoader: {
            alias: webpack_configurator.extendAliases(
                webpack_configurator.easygettext_loader_alias
            ),
        },
    },
];

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
const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "../../src/www/assets/testmanagement/")
);
const manifest_plugin = webpack_configurator.getManifestPlugin();

const webpack_config_for_angular = {
    entry: {
        testmanagement: "./scripts/testmanagement/src/app.js",
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
                // angular-tlp
                angular$: path.resolve(__dirname, "node_modules/angular"),
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

const webpack_config_for_vue_components = {
    entry: {
        "step-definition-field": "./scripts/step-definition-field/index.js",
    },
    context,
    output,
    externals: {
        codendi: "codendi",
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

const entry_points = {
    flamingparrot: "./themes/FlamingParrot/css/style.scss",
};

const colors_burning_parrot = ["orange", "blue", "green", "red", "grey", "purple"];
for (const color of colors_burning_parrot) {
    entry_points[`burningparrot-${color}`] = `./themes/BurningParrot/css/style-${color}.scss`;
    entry_points[
        `burningparrot-${color}-condensed`
    ] = `./themes/BurningParrot/css/style-${color}-condensed.scss`;
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

module.exports = [
    webpack_config_for_angular,
    webpack_config_for_vue_components,
    webpack_config_for_themes,
];

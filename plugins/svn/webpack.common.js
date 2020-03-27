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
const manifest_plugin = webpack_configurator.getManifestPlugin();
const context = __dirname;
const output = webpack_configurator.configureOutput(
    path.resolve(__dirname, "../../src/www/assets/svn")
);

const webpack_config_for_vue_and_themes = {
    entry: {
        "permission-per-group": "./scripts/permissions-per-group/src/index.js",
        "style-fp": "./themes/FlamingParrot/css/style.scss",
        "style-bp": "./themes/BurningParrot/css/style.scss",
    },
    context,
    output,
    externals: {
        tlp: "tlp",
    },
    module: {
        rules: [
            webpack_configurator.rule_scss_loader,
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader,
        ],
    },
    plugins: [
        manifest_plugin,
        webpack_configurator.getVueLoaderPlugin(),
        ...webpack_configurator.getCSSExtractionPlugins(),
    ],
    resolveLoader: { alias: webpack_configurator.easygettext_loader_alias },
};

const webpack_config_for_vanilla = {
    entry: {
        svn: "./scripts/svn.js",
        "svn-admin": "./scripts/svn-admin.js",
    },
    context,
    output,
    externals: {
        codendi: "codendi",
        jquery: "jQuery",
    },
    module: {
        rules: [webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11)],
    },
    plugins: [manifest_plugin],
};

module.exports = [webpack_config_for_vue_and_themes, webpack_config_for_vanilla];

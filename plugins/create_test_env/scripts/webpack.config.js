/*
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
const webpack_configurator = require("../../../tools/utils/scripts/webpack-configurator.js");

const assets_dir_path = path.resolve(__dirname, "../../../src/www/assets/create_test_env/scripts");

const webpack_config = {
    entry: {
        "call-me-back-burning-parrot": "./call-me-back-burning-parrot/src/index.js",
        "call-me-back-flaming-parrot": "./call-me-back-flaming-parrot/src/index.js",
        "call-me-back-admin": "./call-me-back-admin.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: "tlp",
        jquery: "jQuery"
    },
    resolve: {
        alias: webpack_configurator.extendAliases({}, webpack_configurator.tlp_fetch_alias)
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader
        ]
    },
    plugins: [webpack_configurator.getManifestPlugin(), webpack_configurator.getVueLoaderPlugin()],
    resolveLoader: {
        alias: webpack_configurator.extendAliases({}, webpack_configurator.easygettext_loader_alias)
    }
};

module.exports = webpack_config;

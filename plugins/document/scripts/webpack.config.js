/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

const assets_dir_path = path.resolve(__dirname, "../../../src/www/assets/document/scripts");
const assets_public_path = "/assets/document/scripts/";
const MomentTimezoneDataPlugin = require("moment-timezone-data-webpack-plugin");

const webpack_config = {
    entry: {
        document: "./document/index.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path, assets_public_path),
    externals: {
        tlp: "tlp"
    },
    resolve: {
        alias: webpack_configurator.tlp_fetch_alias
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_easygettext_loader,
            webpack_configurator.rule_vue_loader
        ]
    },
    plugins: [
        webpack_configurator.getManifestPlugin(),
        webpack_configurator.getVueLoaderPlugin(),
        webpack_configurator.getMomentLocalePlugin(),
        new MomentTimezoneDataPlugin({
            startYear: 1970,
            endYear: new Date().getFullYear() + 1
        })
    ],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias
    }
};

if (process.env.NODE_ENV === "test" || process.env.NODE_ENV === "watch") {
    webpack_config.devtool = "cheap-eval-source-map";
}

module.exports = webpack_config;

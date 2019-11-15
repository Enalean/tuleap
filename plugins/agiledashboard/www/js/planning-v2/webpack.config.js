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
const webpack_configurator = require("../../../../../tools/utils/scripts/webpack-configurator.js");

const assets_dir_path = path.resolve(
    __dirname,
    "../../../../../src/www/assets/agiledashboard/planning-v2"
);

const webpack_config = {
    entry: {
        "planning-v2": "./src/app/app.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: "tlp",
        jquery: "jQuery",
        ckeditor: "CKEDITOR"
    },
    resolve: {
        alias: webpack_configurator.extendAliases(
            webpack_configurator.tlp_fetch_alias,
            webpack_configurator.angular_tlp_alias,
            {
                // card-fields dependencies
                angular$: path.resolve(__dirname, "node_modules/angular"),
                "angular-sanitize$": path.resolve(__dirname, "node_modules/angular-sanitize"),
                moment$: path.resolve(__dirname, "node_modules/moment"),
                he$: path.resolve(__dirname, "node_modules/he"),
                striptags$: path.resolve(__dirname, "node_modules/striptags"),
                "escape-string-regexp$": path.resolve(
                    __dirname,
                    "node_modules/escape-string-regexp"
                )
            }
        )
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_ng_cache_loader,
            webpack_configurator.rule_vue_loader,
            webpack_configurator.rule_angular_mixed_vue_gettext,
            webpack_configurator.rule_angular_gettext_loader
        ]
    },
    plugins: [
        webpack_configurator.getManifestPlugin(),
        webpack_configurator.getMomentLocalePlugin(),
        webpack_configurator.getVueLoaderPlugin()
    ],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias
    }
};

module.exports = webpack_config;

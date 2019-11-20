/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
    "../../../../../src/www/assets/agiledashboard/scripts"
);
const assets_public_path = "/assets/agiledashboard/scripts/";
const path_to_tlp = path.resolve(__dirname, "../../../../../src/www/themes/common/tlp/");
const manifest_plugin = webpack_configurator.getManifestPlugin();

const webpack_config_for_kanban = {
    entry: {
        kanban: "./src/app/app.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path, assets_public_path),
    externals: {
        tlp: "tlp",
        angular: "angular",
        jquery: "jQuery",
        ckeditor: "CKEDITOR"
    },
    resolve: {
        alias: webpack_configurator.extendAliases(webpack_configurator.tlp_fetch_alias, {
            "angular-tlp": path.join(path_to_tlp, "angular-tlp"),
            // cumulative-flow-chart
            d3$: path.resolve(__dirname, "node_modules/d3"),
            lodash$: path.resolve(__dirname, "node_modules/lodash"),
            moment$: path.resolve(__dirname, "node_modules/moment"),
            // card-fields dependencies
            "angular-sanitize$": path.resolve(__dirname, "node_modules/angular-sanitize"),
            he$: path.resolve(__dirname, "node_modules/he"),
            striptags$: path.resolve(__dirname, "node_modules/striptags"),
            "escape-string-regexp$": path.resolve(__dirname, "node_modules/escape-string-regexp")
        })
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
        manifest_plugin,
        webpack_configurator.getMomentLocalePlugin(),
        webpack_configurator.getVueLoaderPlugin()
    ],
    resolveLoader: {
        alias: webpack_configurator.easygettext_loader_alias
    }
};

const webpack_config_for_angular = {
    entry: {
        angular: "angular"
    },
    output: webpack_configurator.configureOutput(assets_dir_path),
    plugins: [manifest_plugin]
};

module.exports = [webpack_config_for_kanban, webpack_config_for_angular];

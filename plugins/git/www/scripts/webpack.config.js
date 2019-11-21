/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
const assets_public_path = "/plugins/git/assets/";
const manifest_plugin = webpack_configurator.getManifestPlugin();

const path_to_badge = path.resolve(
    __dirname,
    "../../../../src/www/scripts/project/admin/permissions-per-group/"
);

const webpack_config_for_vue = {
    entry: {
        "permission-per-group": "./permissions-per-group/src/index.js",
        "repositories-list": "./repositories/src/index.js",
        repository: "./repository/src/index.js",
        "repository-blob": [
            "./repository/file/syntax-highlight.js",
            "./repository/file/line-highlight.js"
        ]
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path, assets_public_path),
    externals: {
        tlp: "tlp"
    },
    resolve: {
        alias: {
            "permission-badge": path_to_badge
        }
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
        alias: webpack_configurator.easygettext_loader_alias
    }
};

const webpack_config_for_burning_parrot = {
    entry: {
        "admin-gitolite": "./admin-gitolite.js"
    },
    context: path.resolve(__dirname),
    output: webpack_configurator.configureOutput(assets_dir_path),
    externals: {
        tlp: "tlp",
        tuleap: "tuleap"
    },
    module: {
        rules: [
            webpack_configurator.configureBabelRule(webpack_configurator.babel_options_ie11),
            webpack_configurator.rule_po_files
        ]
    },
    plugins: [manifest_plugin, webpack_configurator.getMomentLocalePlugin()]
};

module.exports = [webpack_config_for_vue, webpack_config_for_burning_parrot];

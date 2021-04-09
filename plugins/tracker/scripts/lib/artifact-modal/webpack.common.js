/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
const webpack = require("../../../../../node_modules/webpack");
const RemoveEmptyScriptsPlugin = require("../../../../../node_modules/webpack-remove-empty-scripts");
const MiniCssExtractPlugin = require("../../../../../node_modules/mini-css-extract-plugin");
const webpack_configurator = require("../../../../../tools/utils/scripts/webpack-configurator.js");

const context = __dirname;
const webpack_config = {
    entry: {
        "plugin-tracker-artifact-modal": "./src/index.js",
        style: "./src/tuleap-artifact-modal.scss",
    },
    context,
    output: {
        path: path.join(context, "./dist/"),
        library: "PluginTrackerArtifactModal",
        libraryTarget: "umd",
    },
    externals: {
        tlp: "tlp",
        jquery: "jquery",
        ckeditor4: "ckeditor4",
        angular: "angular",
        "angular-sanitize": "angular-sanitize",
    },
    resolve: {
        extensions: [".js", ".ts"],
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
            webpack_configurator.rule_vue_loader,
            webpack_configurator.rule_scss_loader,
            webpack_configurator.rule_angular_gettext_loader,
            webpack_configurator.rule_ng_cache_loader,
            {
                test: /\.png/,
                type: "asset/inline",
            },
        ],
    },
    plugins: [
        webpack_configurator.getCleanWebpackPlugin(),
        webpack_configurator.getTypescriptCheckerPlugin(true),
        webpack_configurator.getMomentLocalePlugin(),
        new RemoveEmptyScriptsPlugin({ extensions: ["scss", "css"] }),
        new MiniCssExtractPlugin({ filename: "[name].css" }),
        webpack_configurator.getVueLoaderPlugin(),
        new webpack.ProvidePlugin({
            Buffer: ["buffer", "Buffer"],
            process: "process/browser",
        }),
    ],
};

module.exports = [webpack_config];

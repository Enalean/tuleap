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
// eslint-disable-next-line import/no-extraneous-dependencies
const RemoveEmptyScriptsPlugin = require("webpack-remove-empty-scripts");
// eslint-disable-next-line import/no-extraneous-dependencies
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const { webpack_configurator } = require("@tuleap/build-system-configurator");

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
    },
    resolve: {
        extensions: [".js", ".ts"],
        alias: {
            neverthrow: path.resolve(__dirname, "node_modules/neverthrow"),
            hybrids: path.resolve(__dirname, "node_modules/hybrids/src/index.js"),
            "@floating-ui/dom": path.resolve(
                __dirname,
                "../../../../../lib/frontend/lazybox/node_modules/@floating-ui/dom",
            ),
            "tus-js-client": path.resolve(__dirname, "node_modules/tus-js-client"),
            "@tuleap/tlp-fetch": path.resolve(__dirname, "node_modules/@tuleap/tlp-fetch"),
        },
    },
    module: {
        rules: [
            ...webpack_configurator.configureTypescriptRules(),
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
        webpack_configurator.getMomentLocalePlugin(),
        new RemoveEmptyScriptsPlugin({ extensions: ["scss", "css"] }),
        new MiniCssExtractPlugin({ filename: "[name].css" }),
    ],
};

module.exports = [webpack_config];

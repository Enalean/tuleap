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
const webpack = require("webpack");
const { merge } = require("webpack-merge");
const WebpackAssetsManifest = require("webpack-assets-manifest");
const CopyWebpackPlugin = require("copy-webpack-plugin");
const { CleanWebpackPlugin } = require("clean-webpack-plugin");
const RemoveEmptyScriptsPlugin = require("webpack-remove-empty-scripts");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const { VueLoaderPlugin } = require("vue-loader");
const ForkTsCheckerWebpackPlugin = require("fork-ts-checker-webpack-plugin");
const MergeIntoSingleFilePlugin = require("webpack-merge-and-include-globally");
const { ESBuildMinifyPlugin } = require("esbuild-loader");
const rule_configurations = require("./webpack-rule-configs.js");
const { browserlist_config, esbuild_target } = require("./browserslist_config");

function getManifestPlugin() {
    return new WebpackAssetsManifest({
        output: "manifest.json",
        merge: true,
        writeToDisk: true,
    });
}

function getMomentLocalePlugin() {
    // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
    return new webpack.ContextReplacementPlugin(/moment[/\\]locale$/, /fr/);
}

function configureOutput(assets_dir_path, public_path) {
    const output = {
        path: assets_dir_path,
        filename: "[name]-[chunkhash].js",
    };

    if (public_path) {
        output.publicPath = public_path;
    }

    return output;
}

function getCleanWebpackPlugin() {
    return new CleanWebpackPlugin({
        cleanAfterEveryBuildPatterns: ["!css-assets/", "!css-assets/**"],
    });
}

function getVueLoaderPlugin() {
    return new VueLoaderPlugin();
}

function getTypescriptCheckerPlugin(use_vue) {
    return new ForkTsCheckerWebpackPlugin({
        typescript: {
            configOverwrite: {
                exclude: ["**/*.test.ts"],
            },
            extensions: {
                vue: use_vue,
            },
        },
    });
}

function getCopyPlugin(patterns = [], options = {}) {
    return new CopyWebpackPlugin({ patterns, options });
}

function getCSSExtractionPlugins() {
    return [
        new RemoveEmptyScriptsPlugin({
            extensions: ["scss", "css"],
        }),
        new MiniCssExtractPlugin({
            filename: "[name]-[chunkhash].css",
        }),
    ];
}

function getJSAndCSSOptimizerPlugin() {
    return new ESBuildMinifyPlugin({
        target: esbuild_target,
        css: true,
        legalComments: "none",
        exclude: [/including-prototypejs/], // Workaround for Prototype.js code depending on $super
    });
}

function getLegacyConcatenatedScriptsPlugins(concatenated_files_configuration) {
    return [new MergeIntoSingleFilePlugin({ files: concatenated_files_configuration, hash: true })];
}

function getIgnorePlugin() {
    return new webpack.IgnorePlugin({ resourceRegExp: /\.(?:pot|mo|po~)$/ });
}

function extendDevConfiguration(webpack_configs) {
    return webpack_configs.map((webpack_config) =>
        merge(webpack_config, {
            mode: "development",
            target: "browserslist:" + browserlist_config,
            devtool: "inline-source-map",
            plugins: [getIgnorePlugin()],
        })
    );
}

function extendProdConfiguration(webpack_configs) {
    return webpack_configs.map((webpack_config) =>
        merge(webpack_config, {
            mode: "production",
            target: "browserslist:" + browserlist_config,
            optimization: {
                minimizer: [getJSAndCSSOptimizerPlugin()],
            },
            plugins: [getIgnorePlugin()],
            stats: {
                all: false,
                assets: true,
                relatedAssets: true,
                errors: true,
                errorDetails: true,
                performance: true,
                timings: true,
                excludeAssets: [/\.d\.ts(\.map)?$/],
                assetsSpace: Infinity,
                groupAssetsByPath: true,
                groupAssetsByExtension: true,
            },
        })
    );
}

const easygettext_loader_alias = {
    "easygettext-loader": path.resolve(__dirname, "./easygettext-loader.js"),
};

const configurator = {
    configureOutput,
    getCopyPlugin,
    getManifestPlugin,
    getMomentLocalePlugin,
    getVueLoaderPlugin,
    getTypescriptCheckerPlugin,
    getCleanWebpackPlugin,
    getCSSExtractionPlugins,
    getLegacyConcatenatedScriptsPlugins,
    extendDevConfiguration,
    extendProdConfiguration,
    easygettext_loader_alias,
};
Object.assign(configurator, rule_configurations);

module.exports = configurator;

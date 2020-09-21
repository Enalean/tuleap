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

const webpack = require("webpack");
const { merge } = require("webpack-merge");
const WebpackAssetsManifest = require("webpack-assets-manifest");
const CopyWebpackPlugin = require("copy-webpack-plugin");
const { CleanWebpackPlugin } = require("clean-webpack-plugin");
const FixStyleOnlyEntriesPlugin = require("webpack-fix-style-only-entries");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const { VueLoaderPlugin } = require("vue-loader");
const ForkTsCheckerWebpackPlugin = require("fork-ts-checker-webpack-plugin");
const MergeIntoSingleFilePlugin = require("webpack-merge-and-include-globally");
const { SuppressNullNamedEntryPlugin } = require("./webpack-custom-plugins.js");

const rule_configurations = require("./webpack-rule-configs.js");
const aliases = require("./webpack-aliases.js");

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
        new FixStyleOnlyEntriesPlugin({
            extensions: ["scss", "css"],
            silent: true,
        }),
        new MiniCssExtractPlugin({
            filename: "[name]-[chunkhash].css",
        }),
    ];
}

function getCSSOptimizerPlugin() {
    return new CssMinimizerPlugin({
        minimizerOptions: {
            preset: [
                "default",
                {
                    discardComments: { removeAll: true },
                },
            ],
        },
    });
}

function getLegacyConcatenatedScriptsPlugins(concatenated_files_configuration) {
    return [
        new SuppressNullNamedEntryPlugin(),
        new MergeIntoSingleFilePlugin({ files: concatenated_files_configuration, hash: true }),
    ];
}

function getIgnorePlugin() {
    return new webpack.IgnorePlugin(/\.(?:pot|mo|po~)$/);
}

function extendDevConfiguration(webpack_configs) {
    return webpack_configs.map((webpack_config) =>
        merge(webpack_config, {
            mode: "development",
            devtool: "inline-source-map",
            plugins: [getIgnorePlugin()],
        })
    );
}

function extendProdConfiguration(webpack_configs) {
    return webpack_configs.map((webpack_config) =>
        merge(webpack_config, {
            mode: "production",
            plugins: [getCSSOptimizerPlugin(), getIgnorePlugin()],
            stats: {
                all: false,
                assets: true,
                errors: true,
                errorDetails: true,
                performance: true,
                timings: true,
                excludeAssets: [/polyfill-/],
            },
        })
    );
}

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
};
Object.assign(configurator, rule_configurations, aliases);

module.exports = configurator;

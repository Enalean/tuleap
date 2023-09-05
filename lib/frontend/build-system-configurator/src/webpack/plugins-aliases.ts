/**
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

import * as path from "path";
// eslint-disable-next-line import/no-extraneous-dependencies
import webpack from "webpack";
// eslint-disable-next-line import/no-extraneous-dependencies
import { merge } from "webpack-merge";
// eslint-disable-next-line import/no-extraneous-dependencies
import WebpackAssetsManifest from "webpack-assets-manifest";
// eslint-disable-next-line import/no-extraneous-dependencies
import CopyWebpackPlugin from "copy-webpack-plugin";
// eslint-disable-next-line import/no-extraneous-dependencies
import { CleanWebpackPlugin } from "clean-webpack-plugin";
// eslint-disable-next-line import/no-extraneous-dependencies
import RemoveEmptyScriptsPlugin from "webpack-remove-empty-scripts";
// eslint-disable-next-line import/no-extraneous-dependencies
import MiniCssExtractPlugin from "mini-css-extract-plugin";
// eslint-disable-next-line import/no-extraneous-dependencies
import { VueLoaderPlugin } from "vue-loader";
// eslint-disable-next-line import/no-extraneous-dependencies
import ForkTsCheckerWebpackPlugin from "fork-ts-checker-webpack-plugin";
// eslint-disable-next-line import/no-extraneous-dependencies
import MergeIntoSingleFilePlugin from "webpack-merge-and-include-globally";
// eslint-disable-next-line import/no-extraneous-dependencies
import { ESBuildMinifyPlugin } from "esbuild-loader";
import { browserlist_config, esbuild_target } from "../browserslist_config";

export function getManifestPlugin(): typeof WebpackAssetsManifest {
    return new WebpackAssetsManifest({
        output: "manifest.json",
        merge: true,
        writeToDisk: true,
    });
}

export function getMomentLocalePlugin(): webpack.ContextReplacementPlugin {
    // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
    return new webpack.ContextReplacementPlugin(/moment[/\\]locale$/, /fr/);
}

interface OutputResult {
    path: string;
    filename: string;
    publicPath: string | undefined;
}

export function configureOutput(assets_dir_path: string, public_path = ""): OutputResult {
    const output: OutputResult = {
        path: assets_dir_path,
        filename: "[name]-[chunkhash].js",
        publicPath: undefined,
    };

    if (public_path) {
        output.publicPath = public_path;
    }

    return output;
}

export function getCleanWebpackPlugin(): CleanWebpackPlugin {
    return new CleanWebpackPlugin({
        cleanAfterEveryBuildPatterns: ["!css-assets/", "!css-assets/**"],
    });
}

export function getVueLoaderPlugin(): VueLoaderPlugin {
    return new VueLoaderPlugin();
}

export function getTypescriptCheckerPlugin(use_vue: boolean): ForkTsCheckerWebpackPlugin {
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

export function getCopyPlugin(patterns = [], options = {}): CopyWebpackPlugin {
    return new CopyWebpackPlugin({ patterns, options });
}

export function getCSSExtractionPlugins(): object[] {
    return [
        new RemoveEmptyScriptsPlugin({
            extensions: ["scss", "css"],
        }),
        new MiniCssExtractPlugin({
            filename: "[name]-[chunkhash].css",
        }),
    ];
}

function getJSAndCSSOptimizerPlugin(): ESBuildMinifyPlugin {
    return new ESBuildMinifyPlugin({
        target: esbuild_target,
        css: true,
        legalComments: "none",
        exclude: [/including-prototypejs/], // Workaround for Prototype.js code depending on $super
    });
}

export function getLegacyConcatenatedScriptsPlugins(concatenated_files_configuration: {
    [key: string]: string[];
}): object[] {
    return [new MergeIntoSingleFilePlugin({ files: concatenated_files_configuration, hash: true })];
}

export function getIgnorePlugin(): webpack.IgnorePlugin {
    return new webpack.IgnorePlugin({ resourceRegExp: /\.(?:pot|mo|po~)$/ });
}

export function extendDevConfiguration(webpack_configs: object[]): object {
    return webpack_configs.map((webpack_config) =>
        merge(webpack_config, {
            mode: "development",
            target: "browserslist:" + browserlist_config,
            devtool: "inline-source-map",
            plugins: [getIgnorePlugin()],
        }),
    );
}

export function extendProdConfiguration(webpack_configs: object[]): object {
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
        }),
    );
}

export const easygettext_loader_alias = {
    "easygettext-loader": path.resolve(
        __dirname,
        "./../../../../../tools/utils/scripts/easygettext-loader.js",
    ),
};

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

import path from "node:path";
import {fileURLToPath} from "node:url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

import {webpack_configurator} from "@tuleap/build-system-configurator";
import POGettextPlugin from "@tuleap/po-gettext-plugin";

const assets_dir_path = path.resolve(__dirname, "./frontend-assets");
const assets_public_path = "/assets/document/";
import MomentTimezoneDataPlugin from "moment-timezone-data-webpack-plugin";
import {VueLoaderPlugin} from "vue-loader";

const entry_points = {
    document: "./scripts/document/index.js",
    "admin-search-view": "./scripts/admin-search-view/index.ts",
    "document-style": "./themes/document.scss",
};

export default [
    {
        entry: entry_points,
        context: path.resolve(__dirname),
        output: webpack_configurator.configureOutput(assets_dir_path, assets_public_path),
        externals: {
            tlp: "tlp",
        },
        resolve: {
            extensions: [".ts", ".js", ".vue"],
            alias: {
                vue: path.resolve(__dirname, "node_modules", "@vue", "compat"),
            },
        },
        module: {
            rules: [
                ...webpack_configurator.configureTypescriptRules(),
                {
                    test: /\.vue$/,
                    exclude: /node_modules/,
                    loader: "vue-loader",
                    options: {
                        compilerOptions: {
                            isCustomElement: tag => {
                                return 'tlp-relative-date' === tag;
                            },
                            compatConfig: {
                                MODE: 3,
                            },
                        },
                    },
                },
                webpack_configurator.rule_scss_loader,
                {
                    test: /new\.(docx|xlsx|pptx)/,
                    type: "asset/resource",
                },
            ],
        },
        plugins: [
            webpack_configurator.getCleanWebpackPlugin(),
            webpack_configurator.getManifestPlugin(),
            POGettextPlugin.webpack(),
            new VueLoaderPlugin(),
            webpack_configurator.getMomentLocalePlugin(),
            new MomentTimezoneDataPlugin({
                startYear: 1970,
                endYear: new Date().getFullYear() + 1,
            }),
            ...webpack_configurator.getCSSExtractionPlugins(),
        ],
    },
];

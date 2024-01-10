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

// eslint-disable-next-line import/no-extraneous-dependencies
import MiniCssExtractPlugin from "mini-css-extract-plugin";
import { browserlist_config, esbuild_target } from "../browserslist_config";

export function configureTypescriptRules(): object[] {
    return [
        {
            test: /\.ts(x?)$/,
            exclude: /node_modules/,
            use: [
                {
                    loader: "esbuild-loader",
                    options: {
                        loader: "tsx",
                        target: esbuild_target,
                    },
                },
            ],
        },
    ];
}

export const rule_vue_loader = {
    test: /\.vue$/,
    exclude: /node_modules/,
    use: [{ loader: "vue-loader" }],
};

export const rule_po_files = {
    test: /\.po$/,
    exclude: /node_modules/,
    use: [{ loader: "json-loader" }, { loader: "po-gettext-loader" }],
};

export const rule_mustache_files = {
    test: /\.mustache$/,
    exclude: /node_modules/,
    type: "asset/source",
};

export const rule_ng_cache_loader = {
    test: /\.html$/,
    exclude: [/node_modules/, /vendor/],
    use: [
        {
            loader: "ng-cache-loader?-url",
        },
    ],
};

export const rule_angular_gettext_loader = {
    test: /\.po$/,
    exclude: [/node_modules/, /vendor/],
    use: [
        { loader: "json-loader" },
        {
            loader: "angular-gettext-loader?browserify=true&format=json",
        },
    ],
};

export const rule_scss_loader = {
    test: /\.scss$/,
    use: [
        MiniCssExtractPlugin.loader,
        {
            loader: "css-loader",
            options: {
                url: {
                    filter: (url: string): boolean => {
                        // Organization logos might be customized by administrators, let's exclude them for now
                        return (
                            !url.endsWith("organization_logo.png") &&
                            !url.endsWith("organization_logo_small.png")
                        );
                    },
                },
            },
        },
        {
            loader: "postcss-loader",
            options: {
                postcssOptions: {
                    plugins: [["autoprefixer", { overrideBrowserslist: browserlist_config }]],
                },
            },
        },
        "sass-loader",
    ],
};

export const rule_css_assets = {
    test: /(\.(webp|png|gif|eot|ttf|woff|woff2|svg))$/,
    type: "asset/resource",
    generator: {
        filename: "css-assets/[name]-[hash][ext][query]",
    },
};

//Workaround to fix the image display in vue, see: https://github.com/vuejs/vue-loader/issues/1612
export const rule_vue_images = {
    test: /(\.(webp|png|gif|svg))$/,
    type: "asset/resource",
    generator: {
        filename: "static/[name]-[hash][ext][query]",
    },
};

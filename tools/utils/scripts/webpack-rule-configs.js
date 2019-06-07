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

const BabelPresetEnv = require("@babel/preset-env").default;
const BabelPluginSyntaxDynamicImport = require("@babel/plugin-syntax-dynamic-import").default;
const BabelPluginRewireExports = require("babel-plugin-rewire-exports").default;
const BabelPluginIstanbul = require("babel-plugin-istanbul").default;
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

const babel_preset_env_ie_config = [
    BabelPresetEnv,
    {
        targets: {
            ie: 11
        },
        modules: false,
        useBuiltIns: "entry",
        corejs: "3"
    }
];

const babel_preset_env_chrome_config = [
    BabelPresetEnv,
    {
        targets: {
            browsers: ["last 2 Chrome versions"]
        },
        modules: false,
        useBuiltIns: "usage",
        corejs: "3"
    }
];

const babel_options_ie11 = {
    presets: [babel_preset_env_ie_config],
    plugins: [BabelPluginSyntaxDynamicImport]
};

const babel_options_karma = {
    env: {
        watch: babel_options_ie11,
        production: babel_options_ie11,
        test: {
            presets: [babel_preset_env_chrome_config],
            plugins: [BabelPluginSyntaxDynamicImport, BabelPluginRewireExports]
        },
        coverage: {
            presets: [babel_preset_env_chrome_config],
            plugins: [
                BabelPluginSyntaxDynamicImport,
                BabelPluginRewireExports,
                [
                    BabelPluginIstanbul,
                    {
                        exclude: ["**/*.spec.js"]
                    }
                ]
            ]
        }
    }
};

function configureBabelRule(babel_options) {
    return {
        test: /\.js$/,
        exclude: [/node_modules/, /vendor/, /bower_components/],
        use: [
            {
                loader: "babel-loader",
                options: babel_options
            }
        ]
    };
}

const rule_vue_loader = {
    test: /\.vue$/,
    exclude: /node_modules/,
    use: [{ loader: "vue-loader" }]
};

const rule_po_files = {
    test: /\.po$/,
    exclude: /node_modules/,
    use: [{ loader: "json-loader" }, { loader: "po-gettext-loader" }]
};

const rule_mustache_files = {
    test: /\.mustache$/,
    exclude: /node_modules/,
    use: { loader: "raw-loader" }
};

const rule_ng_cache_loader = {
    test: /\.html$/,
    exclude: [/node_modules/, /vendor/, /bower_components/],
    use: [
        {
            loader: "ng-cache-loader",
            query: "-url"
        }
    ]
};

const rule_angular_gettext_loader = {
    test: /\.po$/,
    exclude: [/node_modules/, /vendor/, /bower_components/],
    use: [
        { loader: "json-loader" },
        {
            loader: "angular-gettext-loader",
            query: "browserify=true&format=json"
        }
    ]
};

const rule_angular_gettext_extract_loader = {
    test: /src.*\.(js|html)$/,
    exclude: [/node_modules/, /vendor/, /bower_components/],
    use: [
        {
            loader: "angular-gettext-extract-loader",
            query: "pofile=po/template.pot"
        }
    ]
};

const rule_easygettext_loader = {
    test: /\.po$/,
    exclude: /node_modules/,
    use: [{ loader: "json-loader" }, { loader: "easygettext-loader" }]
};

const rule_scss_loader = {
    test: /\.scss$/,
    use: [
        MiniCssExtractPlugin.loader,
        {
            loader: "css-loader",
            options: {
                url: (url, resourcePath) => {
                    // Organization logos might be customized by administrators, let's exclude them for now
                    return (
                        url.endsWith("organization_logo.png") ||
                        url.endsWith("organization_logo_small.png")
                    );
                }
            }
        },
        "sass-loader"
    ]
};

const rule_css_assets = {
    test: /(\.(png|gif|eot|ttf|woff|woff2))|(font\.svg)$/,
    use: [
        {
            loader: "file-loader",
            options: {
                name: "css-assets/[name]-[sha256:hash:hex:16].[ext]"
            }
        }
    ]
};

module.exports = {
    configureBabelRule,
    babel_options_ie11,
    babel_options_karma,
    rule_po_files,
    rule_mustache_files,
    rule_vue_loader,
    rule_ng_cache_loader,
    rule_angular_gettext_loader,
    rule_angular_gettext_extract_loader,
    rule_easygettext_loader,
    rule_scss_loader,
    rule_css_assets
};

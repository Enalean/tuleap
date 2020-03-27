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
const BabelPluginDynamicImportNode = require("babel-plugin-dynamic-import-node");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const path = require("path");

const babel_preset_env_ie_config = [
    BabelPresetEnv,
    {
        targets: {
            ie: 11,
        },
        modules: false,
        useBuiltIns: "entry",
        corejs: "3",
    },
];

const babel_preset_env_chrome_config = [
    BabelPresetEnv,
    {
        targets: {
            browsers: [
                "last 2 Chrome versions",
                "last 2 Firefox versions",
                "Firefox ESR",
                "last 2 Edge versions",
            ],
        },
        modules: false,
        useBuiltIns: "usage",
        corejs: "3",
    },
];

const babel_options_ie11 = {
    presets: [babel_preset_env_ie_config],
};

const babel_options_chrome_firefox = {
    presets: [babel_preset_env_chrome_config],
};

const babel_options_jest = {
    presets: [
        [
            BabelPresetEnv,
            {
                targets: {
                    node: "8",
                },
                corejs: "3",
                useBuiltIns: "usage",
            },
        ],
    ],
    plugins: [BabelPluginDynamicImportNode],
};

function configureBabelRule(babel_options) {
    return {
        test: /\.js$/,
        exclude: [/node_modules/, /vendor/],
        use: [
            {
                loader: "babel-loader",
                options: babel_options,
            },
        ],
    };
}

function configureTypescriptRules(babel_options) {
    return [
        {
            test: /\.ts(x?)$/,
            exclude: /node_modules/,
            use: [
                {
                    loader: "babel-loader",
                    options: babel_options,
                },
                {
                    loader: "ts-loader",
                    options: {
                        appendTsSuffixTo: ["\\.vue$"],
                        transpileOnly: true,
                    },
                },
            ],
        },
    ];
}

const rule_vue_loader = {
    test: /\.vue$/,
    exclude: /node_modules/,
    use: [{ loader: "vue-loader" }],
};

const rule_file_loader_images = {
    test: /\.svg$/,
    exclude: /node_modules/,
    use: [{ loader: "file-loader" }],
};

const rule_po_files = {
    test: /\.po$/,
    exclude: /node_modules/,
    use: [{ loader: "json-loader" }, { loader: "po-gettext-loader" }],
};

const rule_mustache_files = {
    test: /\.mustache$/,
    exclude: /node_modules/,
    use: { loader: "raw-loader" },
};

const rule_ng_cache_loader = {
    test: /\.html$/,
    exclude: [/node_modules/, /vendor/],
    use: [
        {
            loader: "ng-cache-loader",
            query: "-url",
        },
    ],
};

const artifact_modal_vue_initializer_path = path.resolve(
    __dirname,
    "../../../plugins/tracker/scripts/angular-artifact-modal/src/vue-initializer.js"
);

const rule_angular_gettext_loader = {
    test: /\.po$/,
    exclude: [/node_modules/, /vendor/],
    issuer: {
        not: [artifact_modal_vue_initializer_path],
    },
    use: [
        { loader: "json-loader" },
        {
            loader: "angular-gettext-loader",
            query: "browserify=true&format=json",
        },
    ],
};

// This rule is only intended for the progressive migration of an AngularJS App to Vue
const rule_angular_mixed_vue_gettext = {
    test: /\.po$/,
    exclude: [/node_modules/],
    issuer: artifact_modal_vue_initializer_path,
    use: [{ loader: "json-loader" }, { loader: "easygettext-loader" }],
};

const rule_easygettext_loader = {
    test: /\.po$/,
    exclude: /node_modules/,
    use: [{ loader: "json-loader" }, { loader: "easygettext-loader" }],
};

const rule_scss_loader = {
    test: /\.scss$/,
    use: [
        MiniCssExtractPlugin.loader,
        {
            loader: "css-loader",
            options: {
                url: (url) => {
                    // Organization logos might be customized by administrators, let's exclude them for now
                    return (
                        !url.endsWith("organization_logo.png") &&
                        !url.endsWith("organization_logo_small.png")
                    );
                },
            },
        },
        "sass-loader",
    ],
};

const rule_css_assets = {
    test: /(\.(png|gif|eot|ttf|woff|woff2))|(font\.svg)$/,
    use: [
        {
            loader: "file-loader",
            options: {
                name: "css-assets/[name]-[sha256:hash:hex:16].[ext]",
            },
        },
    ],
};

module.exports = {
    configureBabelRule,
    configureTypescriptRules,
    babel_options_ie11,
    babel_options_chrome_firefox,
    babel_options_jest,
    rule_po_files,
    rule_mustache_files,
    rule_vue_loader,
    rule_ng_cache_loader,
    rule_angular_gettext_loader,
    rule_angular_mixed_vue_gettext,
    rule_easygettext_loader,
    rule_scss_loader,
    rule_css_assets,
    rule_file_loader_images,
};
